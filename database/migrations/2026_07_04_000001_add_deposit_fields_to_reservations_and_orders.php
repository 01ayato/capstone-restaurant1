<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'table_deposit_amount')) {
                $table->decimal('table_deposit_amount', 10, 2)->default(500)->after('additional_chairs');
            }
            if (! Schema::hasColumn('reservations', 'preorder_deposit_amount')) {
                $table->decimal('preorder_deposit_amount', 10, 2)->default(0)->after('table_deposit_amount');
            }
            if (! Schema::hasColumn('reservations', 'deposit_total')) {
                $table->decimal('deposit_total', 10, 2)->default(500)->after('preorder_deposit_amount');
            }
            if (! Schema::hasColumn('reservations', 'deposit_payment_method')) {
                $table->string('deposit_payment_method', 30)->nullable()->after('deposit_total');
            }
            if (! Schema::hasColumn('reservations', 'deposit_payment_proof')) {
                $table->string('deposit_payment_proof')->nullable()->after('deposit_payment_method');
            }
            if (! Schema::hasColumn('reservations', 'deposit_payment_reference')) {
                $table->string('deposit_payment_reference')->nullable()->after('deposit_payment_proof');
            }
            if (! Schema::hasColumn('reservations', 'deposit_payment_amount_paid')) {
                $table->decimal('deposit_payment_amount_paid', 10, 2)->nullable()->after('deposit_payment_reference');
            }
            if (! Schema::hasColumn('reservations', 'deposit_status')) {
                $table->string('deposit_status', 30)->default('paid')->after('deposit_payment_amount_paid');
            }
            if (! Schema::hasColumn('reservations', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('deposit_status');
            }
            if (! Schema::hasColumn('reservations', 'applied_order_id')) {
                $table->foreignId('applied_order_id')->nullable()->after('deposit_paid_at')->constrained('orders')->nullOnDelete();
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'reservation_id')) {
                $table->foreignId('reservation_id')->nullable()->after('user_id')->constrained('reservations')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'deposit_credit')) {
                $table->decimal('deposit_credit', 10, 2)->default(0)->after('delivery_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'deposit_credit')) {
                $table->dropColumn('deposit_credit');
            }
            if (Schema::hasColumn('orders', 'reservation_id')) {
                $table->dropConstrainedForeignId('reservation_id');
            }
        });

        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'applied_order_id')) {
                $table->dropConstrainedForeignId('applied_order_id');
            }
            foreach ([
                'deposit_paid_at',
                'deposit_status',
                'deposit_payment_amount_paid',
                'deposit_payment_reference',
                'deposit_payment_proof',
                'deposit_payment_method',
                'deposit_total',
                'preorder_deposit_amount',
                'table_deposit_amount',
            ] as $column) {
                if (Schema::hasColumn('reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
