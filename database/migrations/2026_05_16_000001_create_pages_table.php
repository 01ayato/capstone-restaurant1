<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('page_name');
            $table->string('page_alias');
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->unsignedInteger('sequence_number')->default(0);
            $table->string('icon', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'page_alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
