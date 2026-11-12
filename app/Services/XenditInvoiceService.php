<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditInvoiceService
{
    public function createInvoice(
        string $externalId,
        float $amount,
        string $description,
        string $successRedirectUrl,
        string $failureRedirectUrl,
    ): string {
        $apiKey = config('services.xendit.secret_key');
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Online payment is not configured. Please choose another payment method.');
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->acceptJson()
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => $externalId,
                    'amount' => round($amount, 2),
                    'currency' => config('services.xendit.currency', 'PHP'),
                    'description' => $description,
                    'success_redirect_url' => $successRedirectUrl,
                    'failure_redirect_url' => $failureRedirectUrl,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            throw new RuntimeException('Could not connect to the online payment service. Please try again.', previous: $exception);
        }

        $invoiceUrl = $response->json('invoice_url');
        if (! $response->successful() || ! is_string($invoiceUrl) || $invoiceUrl === '') {
            throw new RuntimeException('Could not create an online payment link. Please try again.');
        }

        return $invoiceUrl;
    }
}
