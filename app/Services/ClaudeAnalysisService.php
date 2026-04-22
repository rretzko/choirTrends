<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAnalysisService
{
    private string $apiKey;

    private string $apiVersion;

    private string $model;

    private bool $useThinking = false;

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
            // Use Sonnet 4.6 for PDFs - has native document support
            $this->model = 'claude-sonnet-4-6';
        } elseif (str_starts_with($content, 'IMAGE_DATA|||')) {
            // Use Haiku 4.5 for images - better vision performance
            $this->model = 'claude-haiku-4-5-20251001';
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
        // Parse PDF_DATA|||mime_type|||base64_data|||orientation
        $parts = explode('|||', $documentData, 4);

        if (count($parts) < 3) {
            throw new \Exception('Invalid document data format');
        }

        [, $mimeType, $base64Data] = $parts;
        $orientation = isset($parts[3]) ? trim($parts[3]) : 'unknown';

        // Enable extended thinking for landscape PDFs to improve column-reading accuracy
        if ($orientation === 'landscape') {
            $this->useThinking = true;
        }

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
            'orientation' => $orientation,
        ]);

        $prompt = $this->buildPrompt($orientation);

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

    private function buildPrompt(string $orientation = 'unknown'): string
    {
        $landscapeInstructions = '';

        if ($orientation === 'landscape') {
            $landscapeInstructions = <<<'LANDSCAPE'

CRITICAL LAYOUT INSTRUCTION — LANDSCAPE BIFOLD PROGRAM:
This PDF is in LANDSCAPE orientation, which means it is a bifold/booklet-style concert program.
Each page displays TWO COLUMNS of content side by side (a left panel and a right panel).

You MUST read each page in this order:
1. LEFT column — read top to bottom completely
2. RIGHT column — read top to bottom completely
3. Then move to the next page

SONG-TO-ENSEMBLE ASSIGNMENT RULES:
- A song belongs to the most recent ensemble header encountered in the left-then-right reading order.
- An ensemble's songs may CONTINUE from the left column into the right column of the same page.
- A song at the TOP of the right column (before any new ensemble header appears in that column) still
  belongs to the LAST ensemble from the left column. Do NOT skip it or reassign it to a later ensemble.
- Only assign a song to a new ensemble when you encounter that ensemble's header ABOVE the song
  in reading order (left column top-to-bottom, then right column top-to-bottom).
- Do NOT assign songs based on musical style, genre, or performer names — assign ONLY by position
  relative to ensemble headers in the reading order.
- Ignore italic quotes, poetry lines, and thematic text interspersed between songs — these are
  decorative program notes, NOT ensemble headers or song titles.

BIFOLD LAYOUT EXAMPLE:
If a page has this layout:
  LEFT COLUMN:              RIGHT COLUMN:
  [Ensemble A header]       [Song 3 title + credits]
  [Song 1]                  [divider symbol]
  [Song 2]                  [Ensemble B header]
                            [Song 4]

The correct reading order is: Ensemble A → Song 1 → Song 2 → Song 3 → Ensemble B → Song 4
So Song 3 belongs to Ensemble A (NOT Ensemble B), because Ensemble A is the last header before Song 3.

Look for decorative divider symbols (ornamental marks, infinity/knot symbols, horizontal lines) that
visually separate one ensemble's set from the next — these indicate ensemble boundaries.

Do NOT read across rows (left-to-right across both columns at the same vertical position).
Do NOT assume a new column means a new ensemble — check for an actual ensemble header.

LANDSCAPE;
        }

        return <<<PROMPT
You are analyzing a concert program document that may have text extracted in an unusual order due to PDF layout issues.
{$landscapeInstructions}
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
4. Director name: The primary or overall musical director for the event. If there is only one director for the whole program, use that name. If multiple directors are listed (e.g., one per ensemble), pick the most prominent or leave null.

For each ensemble, also extract:
- Director: The director/conductor of THAT SPECIFIC ensemble, if the program lists a separate director per ensemble (common in collage concerts and festivals). If the program only has one overall director, leave this null — the UI will fall back to the event-level director_name.

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
      "director": "Director for this ensemble or null",
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
      "director": "Director for this ensemble or null",
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
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', array_filter([
                'model' => $this->model,
                'max_tokens' => 16000,
                'thinking' => $this->useThinking ? [
                    'type' => 'enabled',
                    'budget_tokens' => 10000,
                ] : null,
                'messages' => $messages,
            ]));

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
        // Extract the text content block, skipping any thinking blocks
        $text = null;
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text = $block['text'];
                break;
            }
        }

        if ($text === null) {
            throw new \Exception('Unexpected API response format');
        }

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
                    'director' => null,
                    'songs' => [],
                ];

                continue;
            }

            // Ensure ensemble has required keys
            if (! isset($ensemble['name'])) {
                $data['ensembles'][$index]['name'] = 'Unnamed Ensemble';
            }

            $data['ensembles'][$index]['director'] = $ensemble['director'] ?? null;

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
