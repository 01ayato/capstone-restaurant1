<?php

use App\Models\Module;
use App\Models\Page;
use App\Services\PageInstaller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{page_name: string, page_alias: string, sequence_number: int, icon: string}>
     */
    private array $pages = [
        ['page_name' => 'Menu Items', 'page_alias' => 'menu_items', 'sequence_number' => 1, 'icon' => 'fa-solid fa-utensils'],
        ['page_name' => 'Orders', 'page_alias' => 'orders', 'sequence_number' => 2, 'icon' => 'fa-solid fa-receipt'],
        ['page_name' => 'Customers', 'page_alias' => 'customers', 'sequence_number' => 3, 'icon' => 'fa-solid fa-users'],
        ['page_name' => 'Reservations', 'page_alias' => 'reservations', 'sequence_number' => 4, 'icon' => 'fa-solid fa-calendar-check'],
        ['page_name' => 'Sales Reports', 'page_alias' => 'sales_reports', 'sequence_number' => 5, 'icon' => 'fa-solid fa-chart-line'],
        ['page_name' => 'Restaurant Info', 'page_alias' => 'restaurant_info', 'sequence_number' => 6, 'icon' => 'fa-solid fa-store'],
        ['page_name' => 'View Website', 'page_alias' => 'view_website', 'sequence_number' => 7, 'icon' => 'fa-solid fa-globe'],
    ];

    public function up(): void
    {
        if (Module::query()->where('alias', 'smoke_n_grill')->exists()) {
            return;
        }

        $module = Module::query()->create([
            'name' => "Smoke n' Grill",
            'alias' => 'smoke_n_grill',
            'sequence' => 0,
            'is_active' => true,
            'sidebar_expand_children' => true,
        ]);

        $roleIds = DB::table('roles')->pluck('id')->all();
        $now = now();

        if ($roleIds !== []) {
            foreach ($roleIds as $roleId) {
                DB::table('module_role')->insert([
                    'module_id' => $module->id,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $pageInstaller = app(PageInstaller::class);

        foreach ($this->pages as $pageData) {
            $page = Page::query()->create([
                'page_name' => $pageData['page_name'],
                'page_alias' => $pageData['page_alias'],
                'module_id' => $module->id,
                'sequence_number' => $pageData['sequence_number'],
                'icon' => $pageData['icon'],
                'is_active' => true,
            ]);

            if ($roleIds !== []) {
                foreach ($roleIds as $roleId) {
                    DB::table('page_role')->insert([
                        'page_id' => $page->id,
                        'role_id' => $roleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $pageInstaller->syncPageWebRoutes($page->fresh('module'));
        }
    }

    public function down(): void
    {
        $module = Module::query()->where('alias', 'smoke_n_grill')->first();
        if ($module === null) {
            return;
        }

        Page::query()->where('module_id', $module->id)->delete();
        $module->delete();
    }
};
