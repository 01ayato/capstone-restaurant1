<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dining_tables', function (Blueprint $table): void {
            $table->decimal('map_x', 5, 2)->nullable()->after('sort_order');
            $table->decimal('map_y', 5, 2)->nullable()->after('map_x');
        });
    }

    public function down(): void
    {
        Schema::table('dining_tables', function (Blueprint $table): void {
            $table->dropColumn(['map_x', 'map_y']);
        });
    }
};
