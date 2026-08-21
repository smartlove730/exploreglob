<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiCaptionService
{
    public function generateCaption(string $prompt, string $imageUrl): string
    {
        $apiKey = config('gemini.api_key') ?? env('AIAPIKEY');

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = config('gemini.model', 'gemma-3-27b');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $aiPrompt = "Write a social media caption from this prompt:\n{$prompt}\n\nImage URL: {$imageUrl}\n\nRequirements:\n- Natural, engaging, concise tone.\n- Add relevant hashtags at the end.\n- Keep caption <= 2000 chars.\n\nReturn strict JSON: {\"caption\":\"...\"}";

        $response = Http::timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($apiUrl, [
                'contents' => [['parts' => [['text' => $aiPrompt]]]],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to generate caption from AI provider.');
        }

        $rawText = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $rawText = trim(preg_replace('/^```json\s*|\s*```$/', '', $rawText) ?? $rawText);

        $decoded = json_decode($rawText, true);
        $caption = trim((string) ($decoded['caption'] ?? ''));

        if ($caption === '') {
            throw new RuntimeException('AI returned an invalid caption payload.');
        }

        return mb_substr($caption, 0, 2200);
    }
}
