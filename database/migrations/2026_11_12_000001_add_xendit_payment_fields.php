<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('paid')->after('payment_amount_paid');
            $table->string('xendit_invoice_id')->nullable()->unique()->after('payment_status');
            $table->text('xendit_invoice_url')->nullable()->after('xendit_invoice_id');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('xendit_invoice_id')->nullable()->unique()->after('deposit_paid_at');
            $table->text('xendit_invoice_url')->nullable()->after('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropUnique(['xendit_invoice_id']);
            $table->dropColumn(['xendit_invoice_id', 'xendit_invoice_url']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['xendit_invoice_id']);
            $table->dropColumn(['xendit_invoice_url', 'xendit_invoice_id', 'payment_status']);
        });
    }
};
