<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('alias', 'smoke_n_grill')->value('id');
        if ($moduleId === null) {
            return;
        }

        DB::table('pages')
            ->where('module_id', $moduleId)
            ->where('page_alias', 'pos')
            ->update(['icon' => 'fa-solid fa-cash-register', 'updated_at' => now()]);
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('alias', 'smoke_n_grill')->value('id');
        if ($moduleId === null) {
            return;
        }

        DB::table('pages')
            ->where('module_id', $moduleId)
            ->where('page_alias', 'pos')
            ->update(['icon' => '', 'updated_at' => now()]);
    }
};
