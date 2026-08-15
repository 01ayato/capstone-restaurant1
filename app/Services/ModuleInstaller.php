<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\File;

class ModuleInstaller
{
    public function __construct(
        private PageInstaller $pageInstaller,
        private RegistryLogService $registryLog,
    ) {}

    public function install(Module $module): void
    {
        $this->ensureModuleScaffold($module);
        $this->syncModulesRegistryLog();
    }

    /**
     * After a module row is updated: move code/views if alias changed, refresh routes, refresh child page routes, rewrite sys_module.log.
     *
     * @param  array{alias: string}  $before
     */
    public function syncAfterModuleUpdate(Module $module, array $before): void
    {
        $oldAlias = $before['alias'];
        $newAlias = $module->alias;

        $oldModuleDir = base_path('modules/'.$oldAlias);
        $newModuleDir = base_path('modules/'.$newAlias);
        $oldViewDir = resource_path('views/modules/'.$oldAlias);
        $newViewDir = resource_path('views/modules/'.$newAlias);

        if ($oldModuleDir !== $newModuleDir && File::exists($oldModuleDir)) {
            File::ensureDirectoryExists(dirname($newModuleDir));
            if (File::exists($newModuleDir)) {
                File::deleteDirectory($newModuleDir);
            }
            File::moveDirectory($oldModuleDir, $newModuleDir, true);
            if ($oldAlias !== $newAlias) {
                $staleBase = $newModuleDir.'/'.$oldAlias.'.php';
                if (File::exists($staleBase)) {
                    File::delete($staleBase);
                }
            }
        } elseif (! File::exists($newModuleDir)) {
            File::ensureDirectoryExists($newModuleDir);
        }

        if ($oldViewDir !== $newViewDir && File::exists($oldViewDir)) {
            File::ensureDirectoryExists(dirname($newViewDir));
            if (File::exists($newViewDir)) {
                File::deleteDirectory($newViewDir);
            }
            File::moveDirectory($oldViewDir, $newViewDir, true);
        } elseif (! File::exists($newViewDir)) {
            File::ensureDirectoryExists($newViewDir);
            $indexView = $newViewDir.'/index.blade.php';
            if (! File::exists($indexView)) {
                File::put($indexView, $this->viewContents($module->name, $newAlias));
            }
        }

        File::ensureDirectoryExists($newModuleDir);
        File::put($newModuleDir.'/'.$newAlias.'.php', $this->baseFileContents($newAlias, $module->name, (string) $module->sequence));
        File::put($newModuleDir.'/web.php', $this->routesFileContents($newAlias));

        $module->load('pages');
        foreach ($module->pages as $page) {
            $this->pageInstaller->syncPageWebRoutes($page);
        }

        $this->syncModulesRegistryLog();
    }

    /**
     * Append a deleted module to sys_module.log so it can be re-imported on another machine.
     */
    public function archiveDeletedModule(Module $module): void
    {
        $path = config('modules.registry_log');
        File::ensureDirectoryExists(dirname($path));
        File::append($path, $this->formatDeletedRegistryLine($module));
    }

    /**
     * Rewrite storage/logs/sys_module.log (see config('modules.registry_log')) from all modules in the database.
     * Lines prefixed with #deleted are preserved for migration.
     */
    public function syncModulesRegistryLog(): void
    {
        $logDir = storage_path('logs');
        File::ensureDirectoryExists($logDir);

        $path = config('modules.registry_log');
        $archived = $this->registryLog->readModuleArchivedLinesForSync();
        $content = Module::query()
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->map(fn (Module $m) => $this->formatRegistryLine($m))
            ->join('');

        File::put($path, $content.$archived);
    }

    /**
     * Remove generated module code and views (expects child page folders already removed when applicable).
     */
    public function removeModuleFromDisk(string $alias): void
    {
        $codeDir = base_path('modules/'.$alias);
        $viewDir = resource_path('views/modules/'.$alias);

        if (File::isDirectory($codeDir)) {
            File::deleteDirectory($codeDir);
        }

        if (File::isDirectory($viewDir)) {
            File::deleteDirectory($viewDir);
        }
    }

    private function formatRegistryLine(Module $module): string
    {
        $safeName = str_replace([',', "\n", "\r", '@'], ' ', $module->name);

        return $module->alias.','.$safeName.','.$module->sequence.'@'.PHP_EOL;
    }

    private function formatDeletedRegistryLine(Module $module): string
    {
        $safeName = str_replace([',', "\n", "\r", '@'], ' ', $module->name);
        $active = $module->is_active ? '1' : '0';
        $sidebar = $module->sidebar_expand_children ? '1' : '0';

        return '#deleted,'.$module->alias.','.$safeName.','.$module->sequence.','.$active.','.$sidebar.'@'.PHP_EOL;
    }

    private function ensureModuleScaffold(Module $module): void
    {
        $alias = $module->alias;
        $name = $module->name;
        $sequence = (string) $module->sequence;

        $dir = base_path('modules/'.$alias);
        File::ensureDirectoryExists($dir);

        $basePath = $dir.'/'.$alias.'.php';
        if (! File::exists($basePath)) {
            File::put($basePath, $this->baseFileContents($alias, $name, $sequence));
        }

        File::put($dir.'/web.php', $this->routesFileContents($alias));

        $viewDir = resource_path('views/modules/'.$alias);
        File::ensureDirectoryExists($viewDir);
        $indexView = $viewDir.'/index.blade.php';
        if (! File::exists($indexView)) {
            File::put($indexView, $this->viewContents($name, $alias));
        }
    }

    private function baseFileContents(string $alias, string $name, string $sequence): string
    {
        $seqInt = (int) $sequence;
        $nameVar = var_export($name, true);

        return <<<PHP
<?php

declare(strict_types=1);

/**
 * Auto-generated base module file.
 * Alias: {$alias}
 */
return [
    'alias' => '{$alias}',
    'name' => {$nameVar},
    'sequence' => {$seqInt},
];

PHP;
    }

    private function routesFileContents(string $alias): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/{$alias}', function () {
        return view('modules.{$alias}.index');
    })->name('module.{$alias}.index');
});

PHP;
    }

    private function viewContents(string $name, string $alias): string
    {
        $title = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $aliasEsc = htmlspecialchars($alias, ENT_QUOTES, 'UTF-8');

        return <<<BLADE
@extends('layouts.app')

@section('title', '{$title} — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-xl font-bold tracking-tight text-zinc-900">{$title}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Module: :alias', ['alias' => '{$aliasEsc}']) }}</p>
@endsection

@section('content')
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-zinc-600">{{ __('This page was generated when the module was added. Replace this view with your feature UI.') }}</p>
    </div>
@endsection

BLADE;
    }
}
