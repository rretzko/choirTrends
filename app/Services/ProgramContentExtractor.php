<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class ProgramContentExtractor
{
    public function __construct(
        private PdfParser $pdfParser
    ) {}

    public function extract(?UploadedFile $file, ?string $uris, ?int $userId = null): string
    {
        if ($file) {
            return $this->extractFromFile($file, $userId);
        }

        if ($uris) {
            return $this->extractFromUris($uris);
        }

        throw new \Exception('No file or URIs provided for extraction');
    }

    private function extractFromFile(UploadedFile $file, ?int $userId): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->extractFromPdf($file->getRealPath(), $userId),
            'txt' => $this->extractFromText($file->getRealPath()),
            'png', 'jpg', 'jpeg', 'gif', 'webp' => $this->extractFromImage($file->getRealPath()),
            default => throw new \Exception('Unsupported file type: '.$extension),
        };
    }

    private function extractFromPdf(string $path, ?int $userId = null): string
    {
        // Claude's API supports PDFs directly, which preserves the visual layout
        // and structure much better than text extraction.
        // We'll send the PDF as a base64-encoded document.

        try {
            // Verify file exists and is readable
            if (! file_exists($path) || ! is_readable($path)) {
                throw new \Exception('PDF file does not exist or is not readable');
            }

            $pdfData = file_get_contents($path);

            if ($pdfData === false || empty($pdfData)) {
                throw new \Exception('Failed to read PDF file or file is empty');
            }

            // Check file size - 20MB limit accounts for ~33% base64 encoding overhead
            // that pushes the actual API request beyond Claude's max request size
            $sizeInBytes = strlen($pdfData);
            $sizeInMB = round($sizeInBytes / (1024 * 1024), 1);

            if ($sizeInMB > 20) {
                throw new \Exception("PDF file is too large ({$sizeInMB}MB). Maximum size for processing is 20MB.");
            }

            // Detect page orientation from PDF metadata
            $orientation = $this->detectPdfOrientation($path, $userId);

            // Encode as base64 - base64_encode() doesn't add whitespace in PHP
            $base64 = base64_encode($pdfData);

            // Verify the base64 encoding is valid
            if (empty($base64)) {
                throw new \Exception('Failed to encode PDF as base64');
            }

            // Ensure the base64 string only contains valid base64 characters
            if (! preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $base64)) {
                throw new \Exception('Invalid base64 encoding generated');
            }

            // Return a special marker that indicates this is PDF data
            // Format: PDF_DATA|||application/pdf|||base64_data|||orientation
            // Using ||| as delimiter to avoid conflicts with base64 characters
            return "PDF_DATA|||application/pdf|||{$base64}|||{$orientation}";

        } catch (\Throwable $e) {
            Log::warning('ProgramContentExtractor native PDF processing failed, falling back to text extraction', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            ]);

            // Fallback to text extraction if PDF reading fails
            return $this->extractPdfAsText($path, $userId);
        }
    }

    private function detectPdfOrientation(string $path, ?int $userId = null): string
    {
        try {
            Log::info('ProgramContentExtractor parsing PDF for orientation', [
                'user_id' => $userId,
                'file_size_bytes' => filesize($path),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            ]);

            $pdf = $this->pdfParser->parseFile($path);
            $pages = $pdf->getPages();

            Log::info('ProgramContentExtractor PDF parsed for orientation', [
                'user_id' => $userId,
                'page_count' => count($pages),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);

            foreach ($pages as $page) {
                $details = $page->getDetails();

                if (isset($details['MediaBox'])) {
                    $mediaBox = $details['MediaBox'];
                    $width = (float) ($mediaBox[2] ?? 0);
                    $height = (float) ($mediaBox[3] ?? 0);

                    if ($width > 0 && $height > 0) {
                        return $width > $height ? 'landscape' : 'portrait';
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::info('Could not detect PDF orientation', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            ]);
        }

        return 'unknown';
    }

    private function extractPdfAsText(string $path, ?int $userId = null): string
    {
        try {
            Log::info('ProgramContentExtractor parsing PDF as text fallback', [
                'user_id' => $userId,
                'file_size_bytes' => filesize($path),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            ]);

            $pdf = $this->pdfParser->parseFile($path);
            $pages = $pdf->getPages();
            $formattedText = [];

            // Extract text page by page to preserve some structure
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();

                if (! empty(trim($pageText))) {
                    $formattedText[] = '=== PAGE '.($pageNumber + 1)." ===\n".$pageText;
                }
            }

            Log::info('ProgramContentExtractor PDF parsed as text fallback', [
                'user_id' => $userId,
                'page_count' => count($pages),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);

            if (empty($formattedText)) {
                throw new \Exception('No text could be extracted from the PDF');
            }

            return implode("\n\n", $formattedText);
        } catch (\Throwable $e) {
            Log::error('ProgramContentExtractor PDF text fallback failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);

            throw new \Exception('Failed to extract text from PDF: '.$e->getMessage());
        }
    }

    private function extractFromText(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false || empty(trim($content))) {
            throw new \Exception('Failed to read text file or file is empty');
        }

        return $content;
    }

    private function extractFromImage(string $path): string
    {
        // Claude can understand images natively, but we need to resize large images
        // to stay within token limits (typically ~200K tokens input limit)

        // Resize image if needed to reduce token usage
        $resizedPath = $this->resizeImageIfNeeded($path);

        $imageData = file_get_contents($resizedPath);

        if ($imageData === false) {
            throw new \Exception('Failed to read image file');
        }

        // Encode as base64 and ensure no whitespace/newlines
        $base64 = str_replace(["\n", "\r", ' '], '', base64_encode($imageData));
        $mimeType = mime_content_type($resizedPath);

        // Clean up temporary resized file if it was created
        if ($resizedPath !== $path && file_exists($resizedPath)) {
            @unlink($resizedPath);
        }

        // Return a special marker that indicates this is image data
        // Using ||| as delimiter to avoid conflicts with base64 characters
        return "IMAGE_DATA|||{$mimeType}|||{$base64}";
    }

    private function resizeImageIfNeeded(string $path): string
    {
        // Max dimensions to stay within token limits
        // JPEG compression makes images much smaller than PNG
        // 1000x1000 JPEG should be readable and stay under token limits
        $maxWidth = 1000;
        $maxHeight = 1000;

        $imageInfo = getimagesize($path);

        if ($imageInfo === false) {
            return $path; // Can't get image info, return original
        }

        [$width, $height] = $imageInfo;

        // If image is already small enough, return original
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $path;
        }

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        // Create image resource based on type
        $sourceImage = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };

        if ($sourceImage === false) {
            return $path; // Can't create image resource, return original
        }

        // Create new resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize the image
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save to temporary file as JPEG with good quality
        // JPEG is much smaller than PNG for photos/scanned documents
        $tempPath = sys_get_temp_dir().'/resized_'.uniqid().'.jpg';
        imagejpeg($resizedImage, $tempPath, 85); // Quality 85 (good quality, reasonable size)

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $tempPath;
    }

    private function extractFromUris(string $uris): string
    {
        $urls = array_filter(array_map('trim', explode("\n", $uris)));
        $contents = [];

        foreach ($urls as $url) {
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $response = Http::timeout(30)->get($url);

                if ($response->successful()) {
                    $contents[] = "Content from {$url}:\n".strip_tags($response->body());
                }
            } catch (\Exception $e) {
                $contents[] = "Failed to fetch {$url}: ".$e->getMessage();
            }
        }

        if (empty($contents)) {
            throw new \Exception('Failed to fetch content from any of the provided URLs');
        }

        return implode("\n\n---\n\n", $contents);
    }
}
