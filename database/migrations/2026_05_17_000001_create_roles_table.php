<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['slug' => 'superadmin', 'name' => 'Superadmin', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'admin', 'name' => 'Admin', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'user', 'name' => 'Users', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
