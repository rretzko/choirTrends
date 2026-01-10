<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;

class ProgramContentExtractor
{
    public function __construct(
        private PdfParser $pdfParser
    ) {}

    public function extract(?UploadedFile $file, ?string $uris): string
    {
        if ($file) {
            return $this->extractFromFile($file);
        }

        if ($uris) {
            return $this->extractFromUris($uris);
        }

        throw new \Exception('No file or URIs provided for extraction');
    }

    private function extractFromFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->extractFromPdf($file->getRealPath()),
            'txt' => $this->extractFromText($file->getRealPath()),
            'png', 'jpg', 'jpeg', 'gif', 'webp' => $this->extractFromImage($file->getRealPath()),
            default => throw new \Exception('Unsupported file type: '.$extension),
        };
    }

    private function extractFromPdf(string $path): string
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

            // Check file size (Claude API limit is 32MB)
            $sizeInBytes = strlen($pdfData);
            $sizeInMB = $sizeInBytes / (1024 * 1024);

            if ($sizeInMB > 32) {
                throw new \Exception("PDF file is too large ({$sizeInMB}MB). Maximum size is 32MB.");
            }

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
            // Format: PDF_DATA|||application/pdf|||base64_data
            // Using ||| as delimiter to avoid conflicts with base64 characters
            return "PDF_DATA|||application/pdf|||{$base64}";

        } catch (\Exception $e) {
            // Fallback to text extraction if PDF reading fails
            return $this->extractPdfAsText($path);
        }
    }

    private function extractPdfAsText(string $path): string
    {
        try {
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

            if (empty($formattedText)) {
                throw new \Exception('No text could be extracted from the PDF');
            }

            return implode("\n\n", $formattedText);
        } catch (\Exception $e) {
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
