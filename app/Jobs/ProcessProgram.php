<?php

namespace App\Jobs;

use App\Services\ClaudeAnalysisService;
use App\Services\ProgramContentExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
    ) {
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
            if ($this->filePath && Storage::exists($this->filePath)) {
                $fullPath = Storage::path($this->filePath);
                $uploadedFile = new \Illuminate\Http\UploadedFile(
                    $fullPath,
                    basename($this->filePath),
                    Storage::mimeType($this->filePath),
                    null,
                    true
                );
            }

            $content = $contentExtractor->extract($uploadedFile, $this->uris);

            // Limit content size for Claude API (max ~100K characters to stay under token limits)
            $maxChars = 100000;
            if (strlen($content) > $maxChars) {
                Log::info('Content truncated', [
                    'original_length' => strlen($content),
                    'truncated_length' => $maxChars,
                ]);
                $content = substr($content, 0, $maxChars) . "\n\n[Content truncated due to length...]";
            }

            // Analyze with Claude
            $extractedData = $analysisService->analyzeProgram($content);

            // Store results in cache with user-specific key
            cache()->put(
                "program_analysis_{$this->userId}",
                [
                    'status' => 'completed',
                    'data' => $extractedData,
                    'content' => $content,
                ],
                now()->addHours(2)
            );

            // Clean up the temporary file
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

            throw $e;
        }
    }
}
