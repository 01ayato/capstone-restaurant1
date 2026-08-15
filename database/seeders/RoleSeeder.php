<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['slug' => Role::SLUG_SUPERADMIN, 'name' => 'Superadmin'],
            ['slug' => Role::SLUG_ADMIN, 'name' => 'Admin'],
            ['slug' => Role::SLUG_USER, 'name' => 'Users'],
        ];

        foreach ($rows as $row) {
            Role::query()->firstOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name']],
            );
        }
    }
}
