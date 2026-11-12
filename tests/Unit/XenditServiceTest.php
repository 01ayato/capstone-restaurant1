<?php

namespace Tests\Unit;

use App\Services\XenditException;
use App\Services\XenditService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenditServiceTest extends TestCase
{
    public function test_it_creates_a_php_invoice_using_the_test_secret_key(): void
    {
        config(['services.xendit.secret_key' => 'xnd_development-test-key']);
        Http::fake([
            'api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_test_123',
                'invoice_url' => 'https://checkout-staging.xendit.co/web/test',
                'currency' => 'PHP',
                'amount' => 500,
            ]),
        ]);

        $invoice = app(XenditService::class)->createInvoice(
            'reservation-123',
            500,
            'Reservation deposit',
            'https://smoke-n-grill.test/my-reservations',
            'https://smoke-n-grill.test/my-reservations',
        );

        $this->assertSame([
            'id' => 'inv_test_123',
            'url' => 'https://checkout-staging.xendit.co/web/test',
        ], $invoice);

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.xendit.co/v2/invoices'
            && $request['external_id'] === 'reservation-123'
            && (float) $request['amount'] === 500.0
            && $request['currency'] === 'PHP');
    }

    public function test_it_refuses_to_make_requests_with_a_production_secret_key(): void
    {
        config(['services.xendit.secret_key' => 'xnd_production-not-for-tests']);
        Http::fake();

        $this->expectException(XenditException::class);
        $this->expectExceptionMessage('Xendit is not configured with a test-mode secret key.');

        app(XenditService::class)->createInvoice(
            'order-123',
            100,
            'Order',
            'https://smoke-n-grill.test/my-orders',
            'https://smoke-n-grill.test/my-orders',
        );
    }
}
