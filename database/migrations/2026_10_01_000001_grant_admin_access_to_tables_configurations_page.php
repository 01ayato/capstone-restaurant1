<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The "Tables Configurations" page was added via the Page Settings UI (not the module/page
     * seed migrations), so it never received page_role rows. EnsureUserCanViewModulePage denies
     * everyone except superadmin when a page has zero role assignments, which blocked Admin.
     */
    public function up(): void
    {
        $module = Module::query()->where('alias', 'smoke_n_grill')->first();
        $role = Role::query()->where('slug', Role::SLUG_ADMIN)->first();

        if ($module === null || $role === null) {
            return;
        }

        $page = Page::query()
            ->where('module_id', $module->id)
            ->where('page_alias', 'tables_configurations')
            ->first();

        if ($page === null) {
            return;
        }

        $now = now();

        if (! DB::table('module_role')->where('module_id', $module->id)->where('role_id', $role->id)->exists()) {
            DB::table('module_role')->insert([
                'module_id' => $module->id,
                'role_id' => $role->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('page_role')->where('page_id', $page->id)->where('role_id', $role->id)->exists()) {
            DB::table('page_role')->insert([
                'page_id' => $page->id,
                'role_id' => $role->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $module = Module::query()->where('alias', 'smoke_n_grill')->first();
        $role = Role::query()->where('slug', Role::SLUG_ADMIN)->first();

        if ($module === null || $role === null) {
            return;
        }

        $page = Page::query()
            ->where('module_id', $module->id)
            ->where('page_alias', 'tables_configurations')
            ->first();

        if ($page !== null) {
            DB::table('page_role')->where('page_id', $page->id)->where('role_id', $role->id)->delete();
        }
    }
};
