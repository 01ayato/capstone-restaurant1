<?php

namespace App\Observers;

use App\Models\Module;
use App\Services\ModuleInstaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleObserver
{
    /**
     * Must be static: Laravel resolves a new observer instance per eloquent event, so instance
     * properties would not carry updating → updated.
     *
     * @var array<int|string, array{alias: string}>
     */
    private static array $moduleSnapshots = [];

    public function __construct(
        private ModuleInstaller $installer
    ) {}

    public function updating(Module $module): void
    {
        self::$moduleSnapshots[$module->getKey()] = [
            'alias' => (string) $module->getOriginal('alias'),
        ];
    }

    public function created(Module $module): void
    {
        try {
            $this->installer->install($module);
        } catch (\Throwable $e) {
            Log::error('ModuleInstaller failed', [
                'module_id' => $module->id,
                'alias' => $module->alias,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public function updated(Module $module): void
    {
        $key = $module->getKey();
        $snapshot = self::$moduleSnapshots[$key] ?? ['alias' => (string) $module->alias];
        unset(self::$moduleSnapshots[$key]);

        try {
            $this->installer->syncAfterModuleUpdate($module, $snapshot);
        } catch (\Throwable $e) {
            Log::error('ModuleInstaller sync after update failed', [
                'module_id' => $module->id,
                'alias' => $module->alias,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public function deleted(Module $module): void
    {
        try {
            $this->installer->archiveDeletedModule($module);
            $this->installer->removeModuleFromDisk((string) $module->alias);
            DB::afterCommit(fn (): mixed => $this->installer->syncModulesRegistryLog());
        } catch (\Throwable $e) {
            Log::error('ModuleInstaller cleanup after delete failed', [
                'module_id' => $module->id,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
