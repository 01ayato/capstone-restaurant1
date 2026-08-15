<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class PaymentProofOcrService
{
    public function __construct(
        private readonly OcrSpaceClient $ocrSpace,
    ) {}

    /**
     * @return array{reference_number: ?string, amount_paid: ?float, raw_text: string}
     */
    public function extractFromUpload(UploadedFile $file): array
    {
        $text = $this->ocrSpace->parseImage($file);

        return [
            'reference_number' => $this->parseReferenceNumber($text),
            'amount_paid' => $this->parseAmountPaid($text),
            'raw_text' => $text,
        ];
    }

    /**
     * @return array{reference_number: ?string, amount_paid: ?float, raw_text: string}
     */
    public function extractFromPath(string $absolutePath): array
    {
        $text = $this->ocrSpace->parseImage($absolutePath);

        return [
            'reference_number' => $this->parseReferenceNumber($text),
            'amount_paid' => $this->parseAmountPaid($text),
            'raw_text' => $text,
        ];
    }

    public function parseReferenceNumber(string $text): ?string
    {
        $patterns = [
            '/ref\.?\s*no\.?\s*[:\s#-]*([0-9A-Za-z-]{6,})/i',
            '/reference\s*(?:no\.?|number|#)?\s*[:\s-]*([0-9A-Za-z-]{6,})/i',
            '/transaction\s*(?:id|ref(?:erence)?)\s*[:\s#-]*([0-9A-Za-z-]{6,})/i',
            '/confirmation\s*(?:no\.?|number|code)\s*[:\s#-]*([0-9A-Za-z-]{6,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    public function parseAmountPaid(string $text): ?float
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_map('trim', explode("\n", $normalized));

        $skipPattern = '/amount\s+due|discount|exchange\s+rate|sgd|off\(|^-\s*(?:php|₱|p)/i';

        foreach ($lines as $line) {
            if (preg_match($skipPattern, $line)) {
                continue;
            }

            if (preg_match('/(?:PHP|₱|P)\s*([\d,]+\.\d{2})/i', $line, $match)) {
                return (float) str_replace(',', '', $match[1]);
            }
        }

        $amounts = [];

        if (preg_match_all('/(?:PHP|₱|P)\s*([\d,]+\.\d{2})/i', $normalized, $matches)) {
            foreach ($matches[1] as $raw) {
                $amounts[] = (float) str_replace(',', '', $raw);
            }
        }

        if ($amounts === []) {
            return null;
        }

        $head = mb_substr($normalized, 0, 600);

        if (preg_match_all('/(?:PHP|₱|P)\s*([\d,]+\.\d{2})/i', $head, $headMatches)) {
            $headAmounts = array_map(
                fn (string $raw) => (float) str_replace(',', '', $raw),
                $headMatches[1]
            );

            if ($headAmounts !== []) {
                return $headAmounts[0];
            }
        }

        return $amounts[0];
    }
}
