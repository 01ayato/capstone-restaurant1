<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * First-time strict permissions: rows with no pivot data would deny everyone except superadmin.
     * Attach every existing role to modules/pages that still have zero assignments (legacy “open” state).
     */
    public function up(): void
    {
        $roleIds = DB::table('roles')->pluck('id')->all();
        if ($roleIds === []) {
            return;
        }

        $now = now();

        foreach (DB::table('modules')->where('is_active', true)->pluck('id') as $moduleId) {
            if (DB::table('module_role')->where('module_id', $moduleId)->exists()) {
                continue;
            }
            foreach ($roleIds as $roleId) {
                DB::table('module_role')->insert([
                    'module_id' => $moduleId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $pageIds = DB::table('pages')
            ->join('modules', 'modules.id', '=', 'pages.module_id')
            ->where('pages.is_active', true)
            ->where('modules.is_active', true)
            ->pluck('pages.id');

        foreach ($pageIds as $pageId) {
            if (DB::table('page_role')->where('page_id', $pageId)->exists()) {
                continue;
            }
            foreach ($roleIds as $roleId) {
                DB::table('page_role')->insert([
                    'page_id' => $pageId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
