<?php

namespace App\Jobs;

use App\Mail\ProgramProcessed;
use App\Models\User;
use App\Services\ClaudeAnalysisService;
use App\Services\ProgramContentExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProcessProgram implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes

    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $filePath,
        public ?string $uris = null
    ) {}

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        // Rate limiting temporarily disabled for debugging
        // TODO: Re-enable once job processing issues are resolved
        // return [new RateLimited('program-processing')];
        return [];
    }

    /**
     * Execute the job.
     */
    public function handle(
        ProgramContentExtractor $contentExtractor,
        ClaudeAnalysisService $analysisService
    ): void {
        try {
            // Increase memory limit for large PDFs
            ini_set('memory_limit', '1024M');

            // Extract content from file
            $uploadedFile = null;
            $tempFilePath = null;

            Log::info('ProcessProgram starting', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'uris' => $this->uris,
                'file_exists' => $this->filePath ? Storage::exists($this->filePath) : false,
                'storage_disk' => config('filesystems.default'),
            ]);

            if ($this->filePath && Storage::exists($this->filePath)) {
                // For S3 storage (Vapor), we need to download the file to a temp location
                // because Storage::path() only works for local filesystem
                $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
                $tempFilePath = sys_get_temp_dir().'/program_'.uniqid().'.'.$extension;

                // Download file from storage (works for both local and S3)
                $fileContents = Storage::get($this->filePath);
                file_put_contents($tempFilePath, $fileContents);

                $uploadedFile = new \Illuminate\Http\UploadedFile(
                    $tempFilePath,
                    basename($this->filePath),
                    Storage::mimeType($this->filePath),
                    null,
                    true
                );
            }

            $content = $contentExtractor->extract($uploadedFile, $this->uris);

            // For base64-encoded PDFs and images, we cannot truncate the content
            // as it would corrupt the base64 data. Only truncate plain text content.
            $isPdfOrImage = str_starts_with($content, 'PDF_DATA|||') ||
                           str_starts_with($content, 'IMAGE_DATA|||');

            if (! $isPdfOrImage) {
                // Limit text content size for Claude API (max ~100K characters to stay under token limits)
                $maxChars = 100000;
                if (strlen($content) > $maxChars) {
                    Log::info('Content truncated', [
                        'original_length' => strlen($content),
                        'truncated_length' => $maxChars,
                    ]);
                    $content = substr($content, 0, $maxChars)."\n\n[Content truncated due to length...]";
                }
            }

            // Analyze with Claude
            Log::info('ProcessProgram calling Claude API', [
                'user_id' => $this->userId,
                'content_length' => strlen($content),
                'is_pdf_or_image' => $isPdfOrImage,
            ]);

            $extractedData = $analysisService->analyzeProgram($content);

            Log::info('ProcessProgram Claude API response received', [
                'user_id' => $this->userId,
                'has_data' => ! empty($extractedData),
            ]);

            // Store results in cache with user-specific key
            // Note: We don't store the raw content for PDFs/images as it can be very large (20+ MB)
            // and exceed MySQL's max_allowed_packet limit. The extracted data is all we need.
            $cacheData = [
                'status' => 'completed',
                'data' => $extractedData,
            ];

            // Only include content for plain text (not PDF or image data)
            if (! $isPdfOrImage) {
                $cacheData['content'] = $content;
            }

            cache()->put(
                "program_analysis_{$this->userId}",
                $cacheData,
                now()->addHours(2)
            );

            Log::info('ProcessProgram cache updated', [
                'user_id' => $this->userId,
                'cache_key' => "program_analysis_{$this->userId}",
                'status' => 'completed',
            ]);

            // Send success email notification
            $user = User::find($this->userId);
            if ($user) {
                Mail::to($user->email)->send(
                    new ProgramProcessed(
                        programData: $extractedData,
                        success: true
                    )
                );
            }

            // Clean up the temporary local file
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            // Clean up the file from storage (S3 or local)
            if ($this->filePath && Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        } catch (\Exception $e) {
            Log::error('Program processing failed', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
            ]);

            // Store error in cache
            cache()->put(
                "program_analysis_{$this->userId}",
                [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ],
                now()->addHours(2)
            );

            // Send failure email notification
            $user = User::find($this->userId);
            if ($user) {
                Mail::to($user->email)->send(
                    new ProgramProcessed(
                        programData: [],
                        success: false,
                        errorMessage: $e->getMessage()
                    )
                );
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessProgram failed permanently', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'error' => $exception?->getMessage(),
        ]);

        cache()->put(
            "program_analysis_{$this->userId}",
            [
                'status' => 'failed',
                'error' => $exception?->getMessage() ?? 'Job failed after all retry attempts.',
            ],
            now()->addHours(2)
        );

        $user = User::find($this->userId);
        if ($user) {
            Mail::to($user->email)->send(
                new ProgramProcessed(
                    programData: [],
                    success: false,
                    errorMessage: $exception?->getMessage() ?? 'Job failed after all retry attempts.'
                )
            );
        }
    }
}
