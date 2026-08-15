<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RegistryLogService
{
    /**
     * Active log lines in sys_module.log that are not in the database (#deleted lines are archive-only).
     *
     * @return Collection<int, array{alias: string, name: string, sequence: int, is_active: bool, sidebar_expand_children: bool}>
     */
    public function archivedModulesNotInDatabase(): Collection
    {
        $existing = Module::query()->pluck('alias')->all();

        return $this->parseRestorableModuleLines()
            ->filter(fn (array $row): bool => ! in_array($row['alias'], $existing, true))
            ->values();
    }

    /**
     * Active log lines in sysql_pages.log that are not in the database (#deleted lines are archive-only).
     *
     * @return Collection<int, array{page_name: string, page_alias: string, sequence_number: int, icon: ?string, is_active: bool, module_alias: string, module_exists: bool}>
     */
    public function archivedPagesNotInDatabase(): Collection
    {
        $modulesByAlias = Module::query()->pluck('id', 'alias');
        $modulesById = Module::query()->pluck('alias', 'id');

        return $this->parseRestorablePageLines($modulesById)
            ->map(function (array $row) use ($modulesByAlias): array {
                $row['module_exists'] = $modulesByAlias->has($row['module_alias']);

                return $row;
            })
            ->filter(function (array $row) use ($modulesByAlias): bool {
                if ($row['module_alias'] === '') {
                    return ! Page::query()->where('page_alias', $row['page_alias'])->exists();
                }

                if (! $modulesByAlias->has($row['module_alias'])) {
                    return true;
                }

                $moduleId = (int) $modulesByAlias->get($row['module_alias']);

                return ! Page::query()
                    ->where('module_id', $moduleId)
                    ->where('page_alias', $row['page_alias'])
                    ->exists();
            })
            ->values();
    }

    public function restoreModule(string $alias): Module
    {
        $row = $this->parseRestorableModuleLines()
            ->first(fn (array $item): bool => $item['alias'] === $alias);

        if ($row === null) {
            throw new \InvalidArgumentException(__('No active log entry for module :alias was found. #deleted lines cannot be restored.', ['alias' => $alias]));
        }

        if (Module::query()->where('alias', $alias)->exists()) {
            throw new \InvalidArgumentException(__('Module :alias already exists.', ['alias' => $alias]));
        }

        return Module::create([
            'name' => $row['name'],
            'alias' => $row['alias'],
            'sequence' => $row['sequence'],
            'is_active' => $row['is_active'],
            'sidebar_expand_children' => $row['sidebar_expand_children'],
        ]);
    }

    public function restorePage(string $moduleAlias, string $pageAlias): Page
    {
        $modulesById = Module::query()->pluck('alias', 'id');

        $row = $this->parseRestorablePageLines($modulesById)
            ->first(fn (array $item): bool => $item['module_alias'] === $moduleAlias && $item['page_alias'] === $pageAlias);

        if ($row === null) {
            throw new \InvalidArgumentException(__('No active log entry for page :page was found. #deleted lines cannot be restored.', ['page' => $pageAlias]));
        }

        $module = Module::query()->where('alias', $moduleAlias)->first();

        if ($module === null) {
            throw new \InvalidArgumentException(__('Restore module :module first, then restore this page.', ['module' => $moduleAlias]));
        }

        if (Page::query()->where('module_id', $module->id)->where('page_alias', $pageAlias)->exists()) {
            throw new \InvalidArgumentException(__('Page :page already exists in this module.', ['page' => $pageAlias]));
        }

        return Page::create([
            'page_name' => $row['page_name'],
            'page_alias' => $row['page_alias'],
            'module_id' => $module->id,
            'sequence_number' => $row['sequence_number'],
            'icon' => $row['icon'],
            'is_active' => $row['is_active'],
        ]);
    }

    /**
     * Log tail for sys_module.log: #deleted archive rows and orphaned active rows not in the database.
     *
     * @return non-empty-string|string
     */
    public function readModuleArchivedLinesForSync(): string
    {
        $existing = Module::query()->pluck('alias')->all();

        return $this->readLogTailFromFile(
            config('modules.registry_log'),
            function (string $line) use ($existing): bool {
                if (str_starts_with(ltrim($line), '#deleted,')) {
                    $row = $this->parseDeletedModuleLine($line);

                    return $row !== null && ! in_array($row['alias'], $existing, true);
                }

                $row = $this->parseActiveModuleLine($line);
                if ($row === null) {
                    return false;
                }

                return ! in_array($row['alias'], $existing, true);
            },
        );
    }

    /**
     * Log tail for sysql_pages.log: #deleted rows and orphaned active rows not in the database.
     *
     * @return non-empty-string|string
     */
    public function readPageArchivedLinesForSync(): string
    {
        $modulesByAlias = Module::query()->pluck('id', 'alias');
        $modulesById = Module::query()->pluck('alias', 'id');

        return $this->readLogTailFromFile(
            config('modules.pages_registry_log'),
            function (string $line) use ($modulesByAlias, $modulesById): bool {
                if (str_starts_with(ltrim($line), '#deleted,')) {
                    $row = $this->parseDeletedPageLine($line);
                    if ($row === null) {
                        return false;
                    }

                    if (! $modulesByAlias->has($row['module_alias'])) {
                        return true;
                    }

                    $moduleId = (int) $modulesByAlias->get($row['module_alias']);

                    return ! Page::query()
                        ->where('module_id', $moduleId)
                        ->where('page_alias', $row['page_alias'])
                        ->exists();
                }

                $row = $this->parseActivePageLine($line, $modulesById);
                if ($row === null) {
                    return false;
                }

                if ($row['module_alias'] === '') {
                    return ! Page::query()->where('page_alias', $row['page_alias'])->exists();
                }

                if (! $modulesByAlias->has($row['module_alias'])) {
                    return true;
                }

                $moduleId = (int) $modulesByAlias->get($row['module_alias']);

                return ! Page::query()
                    ->where('module_id', $moduleId)
                    ->where('page_alias', $row['page_alias'])
                    ->exists();
            },
        );
    }

    /**
     * @return Collection<int, array{alias: string, name: string, sequence: int, is_active: bool, sidebar_expand_children: bool}>
     */
    private function parseRestorableModuleLines(): Collection
    {
        if (! File::exists(config('modules.registry_log'))) {
            return collect();
        }

        $byAlias = [];

        foreach (preg_split('/\R/u', (string) File::get(config('modules.registry_log'))) as $line) {
            $row = $this->parseActiveModuleLine($line);
            if ($row === null) {
                continue;
            }

            $byAlias[$row['alias']] = $row;
        }

        return collect(array_values($byAlias));
    }

    /**
     * @param  Collection<int|string, string|null>  $modulesById
     * @return Collection<int, array{page_name: string, page_alias: string, sequence_number: int, icon: ?string, is_active: bool, module_alias: string}>
     */
    private function parseRestorablePageLines(Collection $modulesById): Collection
    {
        if (! File::exists(config('modules.pages_registry_log'))) {
            return collect();
        }

        $byKey = [];

        foreach (preg_split('/\R/u', (string) File::get(config('modules.pages_registry_log'))) as $line) {
            $row = $this->parseActivePageLine($line, $modulesById);
            if ($row === null) {
                continue;
            }

            $byKey[$row['module_alias'].'::'.$row['page_alias']] = $row;
        }

        return collect(array_values($byKey));
    }

    /**
     * @return array{alias: string, name: string, sequence: int, is_active: bool, sidebar_expand_children: bool}|null
     */
    private function parseActiveModuleLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#deleted,')) {
            return null;
        }

        $body = rtrim($line, '@');
        $parts = array_map(fn (string $part): string => trim($part), explode(',', $body));
        if (count($parts) < 3) {
            return null;
        }

        $alias = $parts[0];
        if ($alias === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $alias)) {
            return null;
        }

        return [
            'alias' => $alias,
            'name' => $parts[1],
            'sequence' => (int) $parts[2],
            'is_active' => true,
            'sidebar_expand_children' => false,
        ];
    }

    /**
     * @param  Collection<int|string, string|null>  $modulesById
     * @return array{page_name: string, page_alias: string, sequence_number: int, icon: ?string, is_active: bool, module_alias: string, log_module_id: int}|null
     */
    private function parseActivePageLine(string $line, Collection $modulesById): ?array
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#deleted,')) {
            return null;
        }

        $body = rtrim($line, '@');
        if (! preg_match(
            '/^\s*(.+?),\s*([a-z][a-z0-9_]*),\s*(\d+),\s*(\d+),\s*(.*?),\s*([01])(?:,\s*([a-z][a-z0-9_]*))?\s*$/',
            $body,
            $matches
        )) {
            return null;
        }

        $logModuleId = (int) $matches[3];
        $moduleAlias = isset($matches[7]) && $matches[7] !== ''
            ? $matches[7]
            : (string) ($modulesById->get($logModuleId) ?? '');

        if ($moduleAlias === '' && Module::query()->where('alias', $matches[2])->exists()) {
            $moduleAlias = $matches[2];
        }

        if ($moduleAlias === '') {
            $moduleAlias = $this->findModuleAliasInModuleLog($matches[2]) ?? '';
        }

        $icon = trim($matches[5]);

        return [
            'page_name' => trim($matches[1]),
            'page_alias' => $matches[2],
            'sequence_number' => (int) $matches[4],
            'icon' => $icon !== '' ? $icon : null,
            'is_active' => $matches[6] === '1',
            'module_alias' => $moduleAlias,
            'log_module_id' => $logModuleId,
        ];
    }

    /**
     * @return array{alias: string, name: string, sequence: int, is_active: bool, sidebar_expand_children: bool}|null
     */
    private function parseDeletedModuleLine(string $line): ?array
    {
        $line = trim($line);
        if (! str_starts_with($line, '#deleted,')) {
            return null;
        }

        $body = rtrim(substr($line, strlen('#deleted,')), '@');
        $parts = array_map(fn (string $part): string => trim($part), explode(',', $body));
        if (count($parts) < 3) {
            return null;
        }

        $alias = $parts[0];
        if ($alias === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $alias)) {
            return null;
        }

        if (count($parts) >= 5) {
            return [
                'alias' => $alias,
                'name' => $parts[1],
                'sequence' => (int) $parts[2],
                'is_active' => $parts[3] === '1',
                'sidebar_expand_children' => $parts[4] === '1',
            ];
        }

        return [
            'alias' => $alias,
            'name' => $parts[1],
            'sequence' => (int) $parts[2],
            'is_active' => true,
            'sidebar_expand_children' => false,
        ];
    }

    /**
     * @return array{page_name: string, page_alias: string, sequence_number: int, icon: ?string, is_active: bool, module_alias: string}|null
     */
    private function parseDeletedPageLine(string $line): ?array
    {
        $line = trim($line);
        if (! str_starts_with($line, '#deleted,')) {
            return null;
        }

        $body = rtrim(substr($line, strlen('#deleted,')), '@');
        if (! preg_match(
            '/^\s*(.+?),\s*([a-z][a-z0-9_]*),\s*\d+,\s*(\d+),\s*(.*?),\s*([01]),\s*([a-z][a-z0-9_]*)\s*$/',
            $body,
            $matches
        )) {
            return null;
        }

        $icon = trim($matches[4]);

        return [
            'page_name' => trim($matches[1]),
            'page_alias' => $matches[2],
            'sequence_number' => (int) $matches[3],
            'icon' => $icon !== '' ? $icon : null,
            'is_active' => $matches[5] === '1',
            'module_alias' => $matches[6],
        ];
    }

    /**
     * @return non-empty-string|string
     */
    private function readLogTailFromFile(string $path, callable $keepLine): string
    {
        if (! File::exists($path)) {
            return '';
        }

        return collect(preg_split('/\R/u', (string) File::get($path)))
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->filter(fn (string $line): bool => $keepLine($line))
            ->map(fn (string $line): string => rtrim($line).(str_ends_with(rtrim($line), '@') ? '' : '@').PHP_EOL)
            ->join('');
    }

    private function findModuleAliasInModuleLog(string $alias): ?string
    {
        if (! File::exists(config('modules.registry_log'))) {
            return null;
        }

        foreach (preg_split('/\R/u', (string) File::get(config('modules.registry_log'))) as $line) {
            $active = $this->parseActiveModuleLine($line);
            if ($active !== null && $active['alias'] === $alias) {
                return $alias;
            }

            $deleted = $this->parseDeletedModuleLine($line);
            if ($deleted !== null && $deleted['alias'] === $alias) {
                return $alias;
            }
        }

        return null;
    }
}
