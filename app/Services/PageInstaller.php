<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class PageInstaller
{
    public function __construct(
        private RegistryLogService $registryLog,
    ) {}

    /**
     * WealthInvest-style line (twist: 3rd field is modules.id, not alias):
     * page_name, page_alias, module_id, sequence_number, icon, is_active@
     */
    public function install(Page $page): void
    {
        $this->ensurePageScaffold($page);
        $this->syncPagesRegistryLog();
    }

    /**
     * Rewrite the page route file (e.g. after the parent module alias changes).
     */
    public function syncPageWebRoutes(Page $page): void
    {
        $page->loadMissing('module');
        if ($page->module === null) {
            return;
        }

        $moduleAlias = $page->module->alias;
        $pageAlias = $page->page_alias;
        $dir = $this->modulePageCodePath($moduleAlias, $pageAlias);

        if (! File::isDirectory($dir)) {
            return;
        }

        File::put($dir.'/web.php', $this->routesFileContents($moduleAlias, $pageAlias));
    }

    /**
     * After a page row is updated: move folders if module or alias changed, refresh routes/config, rewrite pages log.
     *
     * @param  array{page_alias: string, module_id: int}  $before
     */
    public function syncAfterPageUpdate(Page $page, array $before): void
    {
        $page->loadMissing('module');

        $oldModule = Module::query()->find($before['module_id']);
        if ($oldModule === null || $page->module === null) {
            $this->syncPagesRegistryLog();

            return;
        }

        $oldAlias = $before['page_alias'];
        $newModule = $page->module;
        $newAlias = $page->page_alias;

        $oldModuleDir = $this->modulePageCodePath($oldModule->alias, $oldAlias);
        $newModuleDir = $this->modulePageCodePath($newModule->alias, $newAlias);
        $oldViewDir = $this->modulePageViewPath($oldModule->alias, $oldAlias);
        $newViewDir = $this->modulePageViewPath($newModule->alias, $newAlias);

        if ($oldModuleDir !== $newModuleDir && File::exists($oldModuleDir)) {
            File::ensureDirectoryExists(dirname($newModuleDir));
            if (File::exists($newModuleDir)) {
                File::deleteDirectory($newModuleDir);
            }
            File::moveDirectory($oldModuleDir, $newModuleDir, true);
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
                File::put($indexView, $this->viewContents($page->page_name, $newModule->alias, $newAlias));
            }
        }

        File::put($newModuleDir.'/web.php', $this->routesFileContents($newModule->alias, $newAlias));
        File::put($newModuleDir.'/'.$newAlias.'.php', $this->baseFileContents($page, $newModule->alias));

        $this->syncPagesRegistryLog();
    }

    /**
     * Append a deleted page to sysql_pages.log so it can be re-imported on another machine.
     */
    public function archiveDeletedPage(Page $page): void
    {
        $page->loadMissing('module');

        $path = config('modules.pages_registry_log');
        File::ensureDirectoryExists(dirname($path));
        File::append($path, $this->formatDeletedRegistryLine($page));
    }

    /**
     * Rewrite storage/logs/sysql_pages.log (see config('modules.pages_registry_log')) from all pages in the database.
     * Lines prefixed with #deleted are preserved for migration.
     */
    public function syncPagesRegistryLog(): void
    {
        $logDir = storage_path('logs');
        File::ensureDirectoryExists($logDir);

        $path = config('modules.pages_registry_log');
        $archived = $this->registryLog->readPageArchivedLinesForSync();
        $lines = Page::query()
            ->with('module')
            ->orderBy('module_id')
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->get()
            ->map(fn (Page $p) => $this->formatRegistryLine($p))
            ->join('');

        File::put($path, $lines.$archived);
    }

    /**
     * Remove generated page code and views from disk (after DB row is deleted, call from observer with in-memory model).
     */
    public function removePageFromDisk(Page $page): void
    {
        $module = $page->module;
        if ($module === null && $page->module_id) {
            $module = Module::query()->find($page->module_id);
        }
        if ($module === null) {
            return;
        }

        $moduleAlias = $module->alias;
        $pageAlias = $page->page_alias;

        foreach ([$this->modulePageCodePath($moduleAlias, $pageAlias), $this->modulePageViewPath($moduleAlias, $pageAlias)] as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    private function ensurePageScaffold(Page $page): void
    {
        $module = $page->module;
        if ($module === null) {
            return;
        }

        $moduleAlias = $module->alias;
        $pageAlias = $page->page_alias;
        $dir = $this->modulePageCodePath($moduleAlias, $pageAlias);
        File::ensureDirectoryExists($dir);

        $basePath = $dir.'/'.$pageAlias.'.php';
        if (! File::exists($basePath)) {
            File::put($basePath, $this->baseFileContents($page, $moduleAlias));
        }

        File::put($dir.'/web.php', $this->routesFileContents($moduleAlias, $pageAlias));

        $viewDir = $this->modulePageViewPath($moduleAlias, $pageAlias);
        File::ensureDirectoryExists($viewDir);
        $indexView = $viewDir.'/index.blade.php';
        if (! File::exists($indexView)) {
            File::put($indexView, $this->viewContents($page->page_name, $moduleAlias, $pageAlias));
        }
    }

    private function modulePageCodePath(string $moduleAlias, string $pageAlias): string
    {
        return base_path("modules/{$moduleAlias}/pages/{$pageAlias}");
    }

    private function modulePageViewPath(string $moduleAlias, string $pageAlias): string
    {
        return resource_path("views/modules/{$moduleAlias}/pages/{$pageAlias}");
    }

    private function formatRegistryLine(Page $page): string
    {
        $safeName = str_replace([',', "\n", "\r", '@'], ' ', $page->page_name);
        $safeAlias = str_replace([',', "\n", "\r", '@'], '', $page->page_alias);
        $icon = $page->icon ?? '';
        $icon = str_replace([',', "\n", "\r", '@'], ' ', $icon);
        $active = $page->is_active ? '1' : '0';
        $moduleId = (string) $page->module_id;
        $seq = (string) $page->sequence_number;
        $moduleAlias = str_replace([',', "\n", "\r", '@'], '', (string) ($page->module?->alias ?? ''));

        return "{$safeName}, {$safeAlias}, {$moduleId}, {$seq}, {$icon}, {$active}, {$moduleAlias}@".PHP_EOL;
    }

    private function formatDeletedRegistryLine(Page $page): string
    {
        $safeName = str_replace([',', "\n", "\r", '@'], ' ', $page->page_name);
        $safeAlias = str_replace([',', "\n", "\r", '@'], '', $page->page_alias);
        $icon = $page->icon ?? '';
        $icon = str_replace([',', "\n", "\r", '@'], ' ', $icon);
        $active = $page->is_active ? '1' : '0';
        $moduleId = (string) $page->module_id;
        $seq = (string) $page->sequence_number;
        $moduleAlias = str_replace([',', "\n", "\r", '@'], '', (string) ($page->module?->alias ?? ''));

        return "#deleted, {$safeName}, {$safeAlias}, {$moduleId}, {$seq}, {$icon}, {$active}, {$moduleAlias}@".PHP_EOL;
    }

    private function baseFileContents(Page $page, string $moduleAlias): string
    {
        $nameVar = var_export($page->page_name, true);

        return <<<PHP
<?php

declare(strict_types=1);

/**
 * Auto-generated page scaffold under module: {$moduleAlias}
 */
return [
    'module_alias' => '{$moduleAlias}',
    'page_alias' => '{$page->page_alias}',
    'name' => {$nameVar},
    'sequence_number' => {$page->sequence_number},
];

PHP;
    }

    private function routesFileContents(string $moduleAlias, string $pageAlias): string
    {
        $controllerClass = $this->resolvePageControllerClass($moduleAlias, $pageAlias);
        if ($controllerClass !== null) {
            return $this->controllerRoutesFileContents($moduleAlias, $pageAlias, $controllerClass);
        }

        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/{$moduleAlias}/{$pageAlias}', function () {
        return view('modules.{$moduleAlias}.pages.{$pageAlias}.index');
    })->name('page.{$moduleAlias}.{$pageAlias}.index');
});

PHP;
    }

    private function resolvePageControllerClass(string $moduleAlias, string $pageAlias): ?string
    {
        $moduleNamespace = str_replace('_', '', ucwords($moduleAlias, '_'));
        $pageClass = str_replace('_', '', ucwords($pageAlias, '_'));
        $class = "App\\Http\\Controllers\\Modules\\{$moduleNamespace}\\{$pageClass}Controller";

        return class_exists($class) ? $class : null;
    }

    private function controllerRoutesFileContents(string $moduleAlias, string $pageAlias, string $controllerClass): string
    {
        $shortName = class_basename($controllerClass);
        $basePath = "/{$moduleAlias}/{$pageAlias}";
        $routeNamePrefix = "page.{$moduleAlias}.{$pageAlias}";
        $reflection = new ReflectionClass($controllerClass);
        $routes = [];

        if ($this->hasPublicMethod($reflection, 'index')) {
            $routes[] = "    Route::get('{$basePath}', [{$shortName}::class, 'index'])"
                ."\n        ->name('{$routeNamePrefix}.index');";
        }

        if ($this->hasPublicMethod($reflection, 'store')) {
            $routes[] = "    Route::post('{$basePath}', [{$shortName}::class, 'store'])"
                ."\n        ->name('{$routeNamePrefix}.store');";
        }

        if ($this->hasPublicMethod($reflection, 'update')) {
            $updateMethod = $reflection->getMethod('update');
            $routeParam = $this->resolveRouteModelParameter($updateMethod);
            if ($routeParam === null) {
                $routes[] = "    Route::post('{$basePath}', [{$shortName}::class, 'update'])"
                    ."\n        ->name('{$routeNamePrefix}.update');";
            } else {
                $routes[] = "    Route::patch('{$basePath}/{{$routeParam}}', [{$shortName}::class, 'update'])"
                    ."\n        ->name('{$routeNamePrefix}.update');";
            }
        }

        if ($this->hasPublicMethod($reflection, 'destroy')) {
            $destroyMethod = $reflection->getMethod('destroy');
            $routeParam = $this->resolveRouteModelParameter($destroyMethod) ?? 'id';
            $routes[] = "    Route::delete('{$basePath}/{{$routeParam}}', [{$shortName}::class, 'destroy'])"
                ."\n        ->name('{$routeNamePrefix}.destroy');";
        }

        $routeLines = implode("\n", $routes);

        return <<<PHP
<?php

declare(strict_types=1);

use {$controllerClass};
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
{$routeLines}
});

PHP;
    }

    private function hasPublicMethod(ReflectionClass $reflection, string $method): bool
    {
        return $reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic();
    }

    private function resolveRouteModelParameter(ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $className = $type->getName();
            if ($className === Request::class || is_subclass_of($className, Request::class)) {
                continue;
            }

            if (! is_subclass_of($className, Model::class)) {
                continue;
            }

            return Str::snake(class_basename($className));
        }

        return null;
    }

    private function viewContents(string $pageName, string $moduleAlias, string $pageAlias): string
    {
        $title = htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8');
        $modEsc = htmlspecialchars($moduleAlias, ENT_QUOTES, 'UTF-8');
        $pageEsc = htmlspecialchars($pageAlias, ENT_QUOTES, 'UTF-8');

        return <<<BLADE
@extends('layouts.app')

@section('title', '{$title} — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-xl font-bold tracking-tight text-zinc-900">{$title}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Module: :m / Page: :p', ['m' => '{$modEsc}', 'p' => '{$pageEsc}']) }}</p>
@endsection

@section('content')
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-zinc-600">{{ __('This page was generated when the entry was added in Page settings.') }}</p>
    </div>
@endsection

BLADE;
    }
}
