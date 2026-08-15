<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(RestaurantSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => User::composeDisplayName('Admin', null, 'User', null),
                'password' => 'admin123',
                'email_verified_at' => now(),
                'role_id' => Role::query()->where('slug', Role::SLUG_SUPERADMIN)->value('id'),
            ],
        );
    }
}
