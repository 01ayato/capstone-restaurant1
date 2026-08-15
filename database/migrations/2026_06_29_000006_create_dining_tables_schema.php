<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('map_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('dining_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_group_id')->constrained('table_groups')->cascadeOnDelete();
            $table->string('table_number');
            $table->unsignedTinyInteger('chair_count')->default(4);
            $table->string('sit_code')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['table_group_id', 'table_number']);
        });

        Schema::create('table_occupancies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dining_table_id')->constrained('dining_tables')->cascadeOnDelete();
            $table->unsignedTinyInteger('occupied_chairs')->default(0);
            $table->string('status')->default('available');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_occupancies');
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('table_groups');
    }
};
