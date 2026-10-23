<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->integer('base_prep_time')->default(0)->after('is_available');
            $table->enum('station', ['kitchen', 'cashier'])->default('kitchen')->after('base_prep_time');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropColumn(['station', 'base_prep_time']);
        });
    }
};
