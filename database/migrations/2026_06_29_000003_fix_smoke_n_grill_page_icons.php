<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $icons = [
            'menu_items' => 'fa-solid fa-utensils',
            'orders' => 'fa-solid fa-receipt',
            'customers' => 'fa-solid fa-users',
            'reservations' => 'fa-solid fa-calendar-check',
            'sales_reports' => 'fa-solid fa-chart-line',
            'restaurant_info' => 'fa-solid fa-store',
            'view_website' => 'fa-solid fa-globe',
        ];

        $moduleId = DB::table('modules')->where('alias', 'smoke_n_grill')->value('id');
        if ($moduleId === null) {
            return;
        }

        foreach ($icons as $pageAlias => $iconClass) {
            DB::table('pages')
                ->where('module_id', $moduleId)
                ->where('page_alias', $pageAlias)
                ->update(['icon' => $iconClass, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $icons = [
            'menu_items' => 'fa-utensils',
            'orders' => 'fa-receipt',
            'customers' => 'fa-users',
            'reservations' => 'fa-calendar-check',
            'sales_reports' => 'fa-chart-line',
            'restaurant_info' => 'fa-store',
            'view_website' => 'fa-globe',
        ];

        $moduleId = DB::table('modules')->where('alias', 'smoke_n_grill')->value('id');
        if ($moduleId === null) {
            return;
        }

        foreach ($icons as $pageAlias => $iconClass) {
            DB::table('pages')
                ->where('module_id', $moduleId)
                ->where('page_alias', $pageAlias)
                ->update(['icon' => $iconClass, 'updated_at' => now()]);
        }
    }
};
