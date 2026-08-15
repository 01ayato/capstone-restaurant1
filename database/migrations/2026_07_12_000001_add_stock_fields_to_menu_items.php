<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('menu_items', 'stock_level')) {
                $table->unsignedInteger('stock_level')->nullable()->after('is_available');
            }
            if (! Schema::hasColumn('menu_items', 'reorder_level')) {
                $table->unsignedInteger('reorder_level')->nullable()->after('stock_level');
            }
        });

        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'preorder_items')) {
                $table->json('preorder_items')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            if (Schema::hasColumn('menu_items', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }
            if (Schema::hasColumn('menu_items', 'stock_level')) {
                $table->dropColumn('stock_level');
            }
        });

        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'preorder_items')) {
                $table->dropColumn('preorder_items');
            }
        });
    }
};
