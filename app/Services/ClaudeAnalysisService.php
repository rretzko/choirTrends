<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAnalysisService
{
    private string $apiKey;

    private string $apiVersion;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->apiVersion = config('services.anthropic.api_version');
        $this->model = config('services.anthropic.model');

        if (empty($this->apiKey)) {
            throw new \Exception('Anthropic API key is not configured. Please set ANTHROPIC_API_KEY in your .env file.');
        }
    }

    public function analyzeProgram(string $content): array
    {
        try {
            // Use appropriate model based on content type
            // Sonnet 4 for PDFs (native document support)
            // Haiku for images (better vision performance and lower cost)
            $this->selectModelForContent($content);

            $messages = $this->buildMessages($content);
            $response = $this->sendToClaudeAPI($messages);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Claude API analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Failed to analyze program: '.$e->getMessage());
        }
    }

    private function selectModelForContent(string $content): void
    {
        // Check content type and select appropriate model
        if (str_starts_with($content, 'PDF_DATA|||')) {
            // Use Sonnet 4 for PDFs - has native document support
            $this->model = 'claude-sonnet-4-20250514';
        } elseif (str_starts_with($content, 'IMAGE_DATA|||')) {
            // Use Haiku for images - better vision performance
            $this->model = 'claude-3-haiku-20240307';
        }
        // For text content, use the configured default model
    }

    private function buildMessages(string $content): array
    {
        // Check if this is PDF data
        if (str_starts_with($content, 'PDF_DATA|||')) {
            return $this->buildDocumentMessages($content);
        }

        // Check if this is image data
        if (str_starts_with($content, 'IMAGE_DATA|||')) {
            return $this->buildImageMessages($content);
        }

        return $this->buildTextMessages($content);
    }

    private function buildTextMessages(string $content): array
    {
        $prompt = $this->buildPrompt();

        return [
            [
                'role' => 'user',
                'content' => $prompt."\n\nHere is the concert program content:\n\n".$content,
            ],
        ];
    }

    private function buildDocumentMessages(string $documentData): array
    {
        // Parse PDF_DATA|||mime_type|||base64_data
        $parts = explode('|||', $documentData, 3);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid document data format');
        }

        [, $mimeType, $base64Data] = $parts;

        // Trim any whitespace that might have been introduced
        $base64Data = trim($base64Data);
        $mimeType = trim($mimeType);

        // Validate base64 data
        if (empty($base64Data)) {
            throw new \Exception('Empty base64 data received');
        }

        // Remove any whitespace characters from base64 string
        $base64Data = preg_replace('/\s+/', '', $base64Data);

        // Verify it's valid base64
        if (! preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $base64Data)) {
            throw new \Exception('Invalid base64 format detected in document data');
        }

        // Test decode to verify it's valid
        $decoded = base64_decode($base64Data, true);
        if ($decoded === false) {
            throw new \Exception('Base64 data cannot be decoded');
        }

        Log::info('Sending PDF to Claude', [
            'mime_type' => $mimeType,
            'base64_length' => strlen($base64Data),
            'decoded_size_bytes' => strlen($decoded),
        ]);

        $prompt = $this->buildPrompt();

        return [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'document',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $mimeType,
                            'data' => $base64Data,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt,
                    ],
                ],
            ],
        ];
    }

    private function buildImageMessages(string $imageData): array
    {
        // Handle multi-page PDFs (multiple images separated by |NEXT_PAGE|)
        $pages = explode('|NEXT_PAGE|', $imageData);
        $contentBlocks = [];

        foreach ($pages as $page) {
            // Parse IMAGE_DATA|||mime_type|||base64_data
            $parts = explode('|||', $page, 3);

            if (count($parts) !== 3) {
                continue; // Skip invalid pages
            }

            [, $mimeType, $base64Data] = $parts;

            $contentBlocks[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mimeType,
                    'data' => $base64Data,
                ],
            ];
        }

        if (empty($contentBlocks)) {
            throw new \Exception('No valid image data found');
        }

        // Add the prompt as text after all images
        $contentBlocks[] = [
            'type' => 'text',
            'text' => $this->buildPrompt(),
        ];

        return [
            [
                'role' => 'user',
                'content' => $contentBlocks,
            ],
        ];
    }

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing a concert program document that may have text extracted in an unusual order due to PDF layout issues.

IMPORTANT: The text extraction may list ALL song titles first, followed by ALL ensemble names later in the document. You need to intelligently match songs to ensembles based on context clues.

Your task is to extract information using a TWO-STEP PROCESS:

STEP 1: IDENTIFY ALL ENSEMBLE HEADERS FIRST
Concert programs are structured with ensemble names as SECTION HEADERS (bold, larger text, or standalone lines) that divide the program into sections. Each section contains the songs that specific ensemble will perform.

Examples of ensemble headers:
- "Concert Choir"
- "Women's Ensemble"
- "Chamber Singers"
- "Varsity Treble Choir"

First, scan the entire document and identify ALL ensemble/group names that serve as section headers.

STEP 2: FOR EACH ENSEMBLE, EXTRACT ONLY ITS SONGS
Once you have identified all ensemble headers, go through each ensemble one-by-one. For each ensemble:
- Extract ONLY the songs that appear AFTER that ensemble's header
- STOP when you reach the NEXT ensemble header
- Do NOT include songs from other ensembles

EXAMPLE STRUCTURE:

Concert Choir
Ave Maria - Franz Biebl
Lux Aurumque - Eric Whitacre

Women's Ensemble
The Seal Lullaby - Eric Whitacre
Sure on This Shining Night - Morten Lauridsen

Varsity Choir
O Magnum Mysterium - Morten Lauridsen

CORRECT EXTRACTION:
- Ensemble 1: "Concert Choir" with songs: ["Ave Maria", "Lux Aurumque"]
- Ensemble 2: "Women's Ensemble" with songs: ["The Seal Lullaby", "Sure on This Shining Night"]
- Ensemble 3: "Varsity Choir" with songs: ["O Magnum Mysterium"]

WRONG - DO NOT DO THIS:
- Putting all songs under the first ensemble
- Mixing songs from different ensembles together

ADDITIONAL INFORMATION TO EXTRACT:
1. Event name: The name/title of the concert or performance
2. Event date: The date when the event took place or will take place (format as YYYY-MM-DD if possible)
3. School name: The name of the school, organization, or institution presenting the concert
4. Director name: The name of the musical director, conductor, or choir director

For each song, extract:
- Title: The name of the song/piece
- Composer: The composer of the piece
- Arranger: The arranger (if mentioned, often shown as "arr. Name")
- Notes: Any program notes that immediately follow the song (performance notes, dedications, historical context, etc.)

Return ONLY a valid JSON object with this exact structure:
{
  "event_name": "string or null",
  "event_date": "string or null (YYYY-MM-DD format)",
  "school_name": "string or null",
  "director_name": "string or null",
  "ensembles": [
    {
      "name": "First Ensemble Name",
      "songs": [
        {
          "title": "Song Title",
          "composer": "Composer Name or null",
          "arranger": "Arranger Name or null",
          "notes": "Program notes or null"
        }
      ]
    },
    {
      "name": "Second Ensemble Name",
      "songs": [
        {
          "title": "Another Song Title",
          "composer": "Composer Name or null",
          "arranger": "Arranger Name or null",
          "notes": "Program notes or null"
        }
      ]
    }
  ]
}

CRITICAL RULES:
- Use the TWO-STEP process: First find ALL ensembles, then extract songs for EACH ensemble separately
- Songs belong to the ensemble header that PRECEDES them, not the one that follows
- Each ensemble's songs end when you encounter the next ensemble header
- If you cannot find a particular piece of information, use null for that field
- If no ensembles are found, return an empty array
- Do not include any explanation or additional text, ONLY the JSON object
PROMPT;
    }

    private function sendToClaudeAPI(array $messages): array
    {
        $maxRetries = 3;
        $baseDelay = 2; // seconds

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            // Check if it's a retryable error (rate limit or overloaded)
            $error = $response->json()['error'] ?? null;
            $errorType = $error['type'] ?? 'unknown';
            $isRetryable = $error && in_array($errorType, [
                'rate_limit_error',
                'overloaded_error',
            ]);

            // If it's the last attempt or not a retryable error, throw exception
            if ($attempt === $maxRetries || ! $isRetryable) {
                $message = match ($errorType) {
                    'overloaded_error' => 'The AI service is temporarily busy. Please try again in a few minutes.',
                    'rate_limit_error' => 'Too many requests. Please wait a moment and try again.',
                    default => 'Failed to analyze program: '.($error['message'] ?? 'Unknown error'),
                };

                throw new \Exception($message);
            }

            // Calculate exponential backoff delay (longer for overloaded errors)
            $delay = $errorType === 'overloaded_error'
                ? ($baseDelay * pow(2, $attempt)) + 3
                : $baseDelay * pow(2, $attempt);

            Log::info('Retryable API error, retrying', [
                'error_type' => $errorType,
                'attempt' => $attempt + 1,
                'max_retries' => $maxRetries,
                'delay_seconds' => $delay,
            ]);

            // Wait before retrying
            sleep($delay);
        }

        // This should never be reached, but just in case
        throw new \Exception('The AI service is unavailable after multiple attempts. Please try again later.');
    }

    private function parseResponse(array $response): array
    {
        if (! isset($response['content'][0]['text'])) {
            throw new \Exception('Unexpected API response format');
        }

        $text = $response['content'][0]['text'];

        // Try to extract JSON from the response
        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('No JSON found in API response');
        }

        $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse JSON from API response: '.json_last_error_msg());
        }

        // Validate required keys
        $requiredKeys = ['event_name', 'event_date', 'school_name', 'director_name', 'ensembles'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                $data[$key] = ($key === 'ensembles') ? [] : null;
            }
        }

        // Ensure ensembles is always an array
        if (! is_array($data['ensembles'])) {
            $data['ensembles'] = [];
        }

        // Validate and normalize ensemble structure
        foreach ($data['ensembles'] as $index => $ensemble) {
            // Ensure each ensemble is an array/object
            if (! is_array($ensemble)) {
                $data['ensembles'][$index] = [
                    'name' => (string) $ensemble,
                    'songs' => [],
                ];

                continue;
            }

            // Ensure ensemble has required keys
            if (! isset($ensemble['name'])) {
                $data['ensembles'][$index]['name'] = 'Unnamed Ensemble';
            }

            if (! isset($ensemble['songs']) || ! is_array($ensemble['songs'])) {
                $data['ensembles'][$index]['songs'] = [];
            }

            // Validate and normalize each song
            foreach ($data['ensembles'][$index]['songs'] as $songIndex => $song) {
                if (! is_array($song)) {
                    $data['ensembles'][$index]['songs'][$songIndex] = [
                        'title' => (string) $song,
                        'composer' => null,
                        'arranger' => null,
                        'notes' => null,
                    ];

                    continue;
                }

                // Ensure song has all required fields
                $data['ensembles'][$index]['songs'][$songIndex] = [
                    'title' => $song['title'] ?? 'Untitled',
                    'composer' => $song['composer'] ?? null,
                    'arranger' => $song['arranger'] ?? null,
                    'notes' => $song['notes'] ?? null,
                ];
            }
        }

        return $data;
    }
}
