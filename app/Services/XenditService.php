<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    /**
     * @return array{id: string, url: string}
     */
    public function createInvoice(
        string $externalId,
        float $amount,
        string $description,
        string $successRedirectUrl,
        string $failureRedirectUrl,
    ): array {
        $apiKey = config('services.xendit.secret_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new XenditException('Online payment is not configured. Please try again later.');
        }

        if (! preg_match('/^xnd_development[-_]/i', $apiKey)) {
            throw new XenditException('Xendit is not configured with a test-mode secret key.');
        }

        if ($externalId === '' || ! is_finite($amount) || $amount <= 0) {
            throw new XenditException('The online payment amount or reference is invalid.');
        }

        foreach ([$successRedirectUrl, $failureRedirectUrl] as $redirectUrl) {
            if (
                filter_var($redirectUrl, FILTER_VALIDATE_URL) === false
                || ! in_array(parse_url($redirectUrl, PHP_URL_SCHEME), ['http', 'https'], true)
            ) {
                throw new XenditException('The online payment return URL is invalid.');
            }
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->acceptJson()
                ->timeout(30)
                ->post(config('services.xendit.invoice_url'), [
                    'external_id' => $externalId,
                    'amount' => round($amount, 2),
                    'currency' => 'PHP',
                    'description' => $description,
                    'success_redirect_url' => $successRedirectUrl,
                    'failure_redirect_url' => $failureRedirectUrl,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Xendit invoice request failed', [
                'external_id' => $externalId,
                'error' => $exception->getMessage(),
            ]);

            throw new XenditException('Could not connect to the online payment service. Please try again.', previous: $exception);
        }

        $payload = $response->json();
        $invoiceId = is_array($payload) ? ($payload['id'] ?? null) : null;
        $invoiceUrl = is_array($payload) ? ($payload['invoice_url'] ?? null) : null;
        $invoiceHost = is_string($invoiceUrl) ? parse_url($invoiceUrl, PHP_URL_HOST) : null;
        $invoiceScheme = is_string($invoiceUrl) ? parse_url($invoiceUrl, PHP_URL_SCHEME) : null;

        Log::info('Xendit invoice API response', [
            'external_id' => $externalId,
            'http_status' => $response->status(),
            'invoice_id' => is_string($invoiceId) ? $invoiceId : null,
            'invoice_status' => is_array($payload) ? ($payload['status'] ?? null) : null,
            'currency' => is_array($payload) ? ($payload['currency'] ?? null) : null,
            'amount' => is_array($payload) ? ($payload['amount'] ?? null) : null,
            'invoice_url_host' => is_string($invoiceHost) ? $invoiceHost : null,
            'invoice_url_scheme' => is_string($invoiceScheme) ? $invoiceScheme : null,
        ]);

        if (! $response->successful()) {
            Log::error('Xendit rejected invoice creation', [
                'external_id' => $externalId,
                'http_status' => $response->status(),
                'error_code' => is_array($payload) && is_string($payload['error_code'] ?? null)
                    ? $payload['error_code']
                    : null,
                'message' => is_array($payload) && is_string($payload['message'] ?? null)
                    ? mb_substr($payload['message'], 0, 500)
                    : null,
            ]);

            throw new XenditException('Could not create an online payment link. Please try again.');
        }

        $validInvoiceUrl = is_string($invoiceUrl)
            && $invoiceScheme === 'https'
            && in_array($invoiceHost, ['checkout.xendit.co', 'checkout-staging.xendit.co'], true);

        if (
            ! is_string($invoiceId)
            || $invoiceId === ''
            || ! $validInvoiceUrl
        ) {
            Log::error('Xendit returned an invalid invoice response', [
                'external_id' => $externalId,
                'has_invoice_id' => is_string($invoiceId) && $invoiceId !== '',
                'invoice_url_host' => is_string($invoiceHost) ? $invoiceHost : null,
                'invoice_url_scheme' => is_string($invoiceScheme) ? $invoiceScheme : null,
            ]);

            throw new XenditException('Could not create an online payment link. Please try again.');
        }

        return [
            'id' => $invoiceId,
            'url' => $invoiceUrl,
        ];
    }
}
