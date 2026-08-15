<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OcrSpaceClient
{
    public function parseImage(UploadedFile|string $image): string
    {
        $apiKey = config('services.ocrspace.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OCR.Space API key is not configured.');
        }

        if ($image instanceof UploadedFile) {
            $contents = file_get_contents($image->getRealPath());
            $filename = $image->getClientOriginalName();
        } else {
            $contents = file_get_contents($image);
            $filename = basename($image);
        }

        $response = Http::timeout(60)
            ->attach('file', $contents, $filename)
            ->post(config('services.ocrspace.endpoint'), [
                'apikey' => $apiKey,
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'detectOrientation' => 'true',
                'scale' => 'true',
                'OCREngine' => '2',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OCR request failed.');
        }

        $payload = $response->json();

        if (($payload['IsErroredOnProcessing'] ?? false) === true) {
            $message = $payload['ErrorMessage'][0] ?? $payload['ErrorDetails'] ?? 'OCR processing failed.';

            throw new RuntimeException(is_string($message) ? $message : 'OCR processing failed.');
        }

        $text = $payload['ParsedResults'][0]['ParsedText'] ?? '';

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('No text could be read from the image.');
        }

        return $text;
    }
}
