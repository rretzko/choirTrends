<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;

class ProgramContentExtractor
{
    public function __construct(
        private PdfParser $pdfParser
    ) {
    }

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
            default => throw new \Exception('Unsupported file type: ' . $extension),
        };
    }

    private function extractFromPdf(string $path): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($path);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                throw new \Exception('No text could be extracted from the PDF');
            }

            return $text;
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from PDF: ' . $e->getMessage());
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
        // For now, we'll encode the image as base64 and let Claude process it directly
        // This is actually better than OCR as Claude can understand images natively
        $imageData = file_get_contents($path);

        if ($imageData === false) {
            throw new \Exception('Failed to read image file');
        }

        $base64 = base64_encode($imageData);
        $mimeType = mime_content_type($path);

        // Return a special marker that indicates this is image data
        return "IMAGE_DATA:{$mimeType}:{$base64}";
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
                    $contents[] = "Content from {$url}:\n" . strip_tags($response->body());
                }
            } catch (\Exception $e) {
                $contents[] = "Failed to fetch {$url}: " . $e->getMessage();
            }
        }

        if (empty($contents)) {
            throw new \Exception('Failed to fetch content from any of the provided URLs');
        }

        return implode("\n\n---\n\n", $contents);
    }
}
