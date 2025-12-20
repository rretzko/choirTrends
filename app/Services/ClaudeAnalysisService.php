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
            $messages = $this->buildMessages($content);
            $response = $this->sendToClaudeAPI($messages);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Claude API analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Failed to analyze program: ' . $e->getMessage());
        }
    }

    private function buildMessages(string $content): array
    {
        // Check if this is image data
        if (str_starts_with($content, 'IMAGE_DATA:')) {
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
                'content' => $prompt . "\n\nHere is the concert program content:\n\n" . $content,
            ],
        ];
    }

    private function buildImageMessages(string $imageData): array
    {
        // Parse IMAGE_DATA:mime_type:base64_data
        $parts = explode(':', $imageData, 3);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid image data format');
        }

        [, $mimeType, $base64Data] = $parts;

        $prompt = $this->buildPrompt();

        return [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
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

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing a concert program document. Please extract the following information and return it as a JSON object:

1. Event name: The name/title of the concert or performance
2. Event date: The date when the event took place or will take place
3. School name: The name of the school, organization, or institution presenting the concert
4. Director name: The name of the musical director, conductor, or choir director
5. Ensembles and their songs:

   IMPORTANT: Concert programs are typically structured with ensemble names as SECTION HEADERS followed by the songs that ensemble will perform.

   For example:

   Concert Choir
   Ave Maria - Franz Biebl
   Lux Aurumque - Eric Whitacre

   Women's Ensemble
   The Seal Lullaby - Eric Whitacre
   Sure on This Shining Night - Morten Lauridsen

   In this structure:
   - "Concert Choir" is the first ensemble
   - "Ave Maria" and "Lux Aurumque" belong to Concert Choir
   - "Women's Ensemble" is the second ensemble
   - "The Seal Lullaby" and "Sure on This Shining Night" belong to Women's Ensemble

   For each performing group/ensemble, extract:
   - Ensemble name (e.g., "Concert Choir", "Women's Ensemble", "Chamber Singers")
   - ALL songs that appear AFTER that ensemble name and BEFORE the next ensemble name
   - For each song include:
     * Title: The name of the song/piece
     * Composer: The composer of the piece
     * Arranger: The arranger (if mentioned, often shown as "arr. Name")
     * Notes: Any program notes that immediately follow the song (performance notes, dedications, historical context, etc.)

Please return ONLY a valid JSON object with these exact keys:
{
  "event_name": "string or null",
  "event_date": "string or null (in YYYY-MM-DD format if possible)",
  "school_name": "string or null",
  "director_name": "string or null",
  "ensembles": [
    {
      "name": "Ensemble Name",
      "songs": [
        {
          "title": "Song Title",
          "composer": "Composer Name or null",
          "arranger": "Arranger Name or null",
          "notes": "Program notes or null"
        }
      ]
    }
  ]
}

Important notes:
- Pay careful attention to which songs belong to which ensemble based on their position in the document
- Each ensemble must have at least one song
- Songs belong to the ensemble header that precedes them, not the one that follows
- If you cannot find a particular piece of information, use null for that field
- If no ensembles are found, return an empty array
- Do not include any explanation or additional text, just the JSON object
PROMPT;
    }

    private function sendToClaudeAPI(array $messages): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 1024,
            'messages' => $messages,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Claude API request failed: ' . $response->body());
        }

        return $response->json();
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
            throw new \Exception('Failed to parse JSON from API response: ' . json_last_error_msg());
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
