<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'dining_table_id')) {
                $table->foreignId('dining_table_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('dining_tables')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(120)->after('reserved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'dining_table_id')) {
                $table->dropConstrainedForeignId('dining_table_id');
            }

            if (Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};
