<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'ready_for_pickup')->update(['status' => 'ready']);
        DB::table('orders')->where('status', 'out_for_delivery')->update(['status' => 'ready']);
        DB::table('orders')->where('payment_method', 'cash_on_delivery')->update(['payment_method' => 'cash']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'ready')->update(['status' => 'ready_for_pickup']);
        DB::table('orders')->where('payment_method', 'cash')->update(['payment_method' => 'cash_on_delivery']);
    }
};
