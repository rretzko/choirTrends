# Vapor Upload Debugging Session - 2026-01-29

## Problem Summary

File uploads via the Add Program page (`/add-program`) work on local development but hang on production (choirtrends.com). Vapor logs showed nothing for debugging.

## What We Discovered

### Issue 1: Vapor Signed Storage URL Authentication (FIXED)

The `/vapor/signed-storage-url` endpoint only had `web` middleware, not `auth`. This caused 403 errors when requesting signed S3 upload URLs.

**Fix Applied:**
- Published `config/vapor.php`
- Added `'middleware' => ['web', 'auth']` to ensure authenticated access

### Issue 2: Upload Works But Processing Hangs (PARTIALLY DIAGNOSED)

After fixing Issue 1, uploads succeed but the background job (`ProcessProgram`) sometimes doesn't complete:

- First 1-3 uploads work fine
- Subsequent uploads hang at "Processing your program..." state
- The page polls `/add-program/status` repeatedly but status never changes from 'processing' to 'completed'
- No errors in Vapor logs, no failed jobs in queue

**Suspected Cause:** Rate limiting on the job (2 jobs/minute) combined with job timeout may cause jobs to expire while waiting.

## Changes Made

### 1. `config/vapor.php` (NEW FILE)
```php
'middleware' => ['web', 'auth'],
```

### 2. `app/Jobs/ProcessProgram.php`
- Disabled rate limiting middleware (temporarily for debugging)
- Added detailed logging at key points:
  - Job start
  - Before Claude API call
  - After Claude API response
  - After cache update

### 3. `resources/views/add-program.blade.php`
- Added console.log debugging for form submit
- Improved error handling with specific messages for 401/403/419 errors

## Current State

- **Upload to S3:** Working
- **Form submission:** Working
- **Background job processing:** Intermittently failing (no logs appearing)

## Next Steps

1. **Deploy the latest changes:**
   ```bash
   vapor deploy production
   ```

2. **Test uploads and check CloudWatch logs:**
   - AWS Console → CloudWatch → Log Groups
   - `/aws/lambda/vapor-choirTrends-production-queue`

   Or via CLI:
   ```bash
   vapor logs:cli production
   ```

3. **Look for these log messages:**
   - `ProcessProgram starting` - Job was picked up by queue worker
   - `ProcessProgram calling Claude API` - About to call Claude
   - `ProcessProgram Claude API response received` - Claude responded
   - `ProcessProgram cache updated` - Successfully completed

4. **If no logs appear:** Queue worker isn't picking up jobs
   - Check Vapor Dashboard → Production → Metrics for queue depth
   - Try manually processing a job:
     ```bash
     vapor command production --command="queue:work --once --verbose"
     ```

5. **If logs stop at "calling Claude API":** Claude API issue
   - Check ANTHROPIC_API_KEY is set in Vapor environment
   - Check Claude API rate limits/quotas

## Configuration Reference

### Vapor Environment (Production)
- Cache: DynamoDB (`vapor_cache` table)
- Queue: SQS (`default` queue)
- Storage: S3 (`choirtrends` bucket)
- Filesystem default: `s3`

### Job Settings
- Timeout: 300 seconds (5 minutes)
- Tries: 2
- Rate limiting: DISABLED (was 2/minute)

## Useful Commands

```bash
# Check config
vapor command production --command="config:show filesystems"
vapor command production --command="config:show cache"
vapor command production --command="config:show queue"

# Check failed jobs
vapor command production --command="queue:failed"

# Process one job manually
vapor command production --command="queue:work --once --verbose"

# View logs
vapor logs:cli production
```

## Files Modified

1. `config/vapor.php` - NEW (published from vendor)
2. `app/Jobs/ProcessProgram.php` - Added logging, disabled rate limiting
3. `app/Providers/AppServiceProvider.php` - Has uploadFiles gate and rate limiter definition
4. `resources/views/add-program.blade.php` - Added debugging console.log statements

## To Re-enable Rate Limiting

Once the issue is resolved, uncomment in `app/Jobs/ProcessProgram.php`:

```php
public function middleware(): array
{
    return [new RateLimited('program-processing')];
}
```
