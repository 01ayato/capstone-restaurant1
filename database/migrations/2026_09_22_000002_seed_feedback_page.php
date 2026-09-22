<?php

use App\Models\Module;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::query()->where('alias', 'smoke_n_grill')->first();
        if ($module === null || Page::query()->where('module_id', $module->id)->where('page_alias', 'feedback')->exists()) {
            return;
        }

        $page = Page::query()->create([
            'page_name' => 'Feedback',
            'page_alias' => 'feedback',
            'module_id' => $module->id,
            'sequence_number' => 8,
            'icon' => 'fa-solid fa-star',
            'is_active' => true,
        ]);

        $roleIds = DB::table('roles')->pluck('id')->all();
        $now = now();

        foreach ($roleIds as $roleId) {
            DB::table('page_role')->insert([
                'page_id' => $page->id,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $module = Module::query()->where('alias', 'smoke_n_grill')->first();
        if ($module === null) {
            return;
        }

        Page::query()->where('module_id', $module->id)->where('page_alias', 'feedback')->delete();
    }
};
