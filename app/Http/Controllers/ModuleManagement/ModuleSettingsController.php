<?php

namespace App\Http\Controllers\ModuleManagement;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\RegistryLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuleSettingsController extends Controller
{
    public function __construct(
        private RegistryLogService $registryLog,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $editingModule = null;
        $deletingModule = null;

        if ($request->filled('delete_module')) {
            $deletingModule = Module::query()->with('pages')->find($request->integer('delete_module'));
            if ($deletingModule === null) {
                return redirect()
                    ->route('module-management.module-settings')
                    ->with('error', __('That module no longer exists.'));
            }
        } elseif ($request->filled('edit')) {
            $editingModule = Module::query()->find($request->integer('edit'));
        }

        $modules = Module::query()->with('pages')->orderBy('sequence')->orderBy('id')->get();
        $nextSequence = ((int) Module::query()->max('id')) + 1;
        $restorableModules = $this->registryLog->archivedModulesNotInDatabase();

        return view('module-management.module-settings', [
            'modules' => $modules,
            'nextSequence' => $nextSequence,
            'editingModule' => $editingModule,
            'deletingModule' => $deletingModule,
            'restorableModules' => $restorableModules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:modules,alias'],
            'sequence' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'alias.unique' => __('This alias is already used by another module. Edit the alias or adjust the name so it generates a different value.'),
        ]);

        $sequence = ($validated['sequence'] ?? null) !== null
            ? (int) $validated['sequence']
            : ((int) Module::query()->max('id')) + 1;

        Module::create([
            'name' => $validated['name'],
            'alias' => $validated['alias'],
            'sequence' => $sequence,
            'is_active' => $request->boolean('is_active', true),
            'sidebar_expand_children' => false,
        ]);

        return redirect()
            ->route('module-management.module-settings')
            ->with('success', __('Module added.'));
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('modules', 'alias')->ignore($module->id)],
            'sequence' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'alias.unique' => __('This alias is already used by another module. Choose a different alias.'),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('module-management.module-settings', ['edit' => $module->id])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $sequence = ($validated['sequence'] ?? null) !== null
            ? (int) $validated['sequence']
            : $module->sequence;

        $module->update([
            'name' => $validated['name'],
            'alias' => $validated['alias'],
            'sequence' => $sequence,
            'is_active' => $request->boolean('is_active', true),
            'sidebar_expand_children' => $request->boolean('sidebar_expand_children'),
        ]);

        return redirect()
            ->route('module-management.module-settings')
            ->with('success', __('Module updated.'));
    }

    public function updateSidebar(Module $module, Request $request): RedirectResponse
    {
        $request->validate([
            'sidebar_expand_children' => ['required', 'in:0,1'],
        ]);

        $module->update([
            'sidebar_expand_children' => $request->input('sidebar_expand_children') === '1',
        ]);

        return redirect()
            ->route('module-management.module-settings')
            ->with('success', __('Sidebar behavior updated for :name.', ['name' => $module->name]));
    }

    public function destroy(Request $request, Module $module): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'delete_confirmed' => ['accepted'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('module-management.module-settings', ['delete_module' => $module->id])
                ->withErrors($validator);
        }

        $module->load(['pages']);

        DB::transaction(function () use ($module): void {
            foreach ($module->pages as $page) {
                $page->delete();
            }
            $module->delete();
        });

        return redirect()
            ->route('module-management.module-settings')
            ->with('success', __('Module and its pages were deleted.'));
    }

    public function restoreFromLog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alias' => ['nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'restore_all' => ['sometimes', 'boolean'],
        ]);

        try {
            if ($request->boolean('restore_all')) {
                $rows = $this->registryLog->archivedModulesNotInDatabase();
                if ($rows->isEmpty()) {
                    return redirect()
                        ->route('module-management.module-settings')
                        ->with('info', __('No archived modules in the log need restoring.'));
                }

                foreach ($rows as $row) {
                    $this->registryLog->restoreModule($row['alias']);
                }

                return redirect()
                    ->route('module-management.module-settings')
                    ->with('success', __('Restored :count module(s) from the log.', ['count' => $rows->count()]));
            }

            $alias = (string) ($validated['alias'] ?? '');
            if ($alias === '') {
                return redirect()
                    ->route('module-management.module-settings')
                    ->with('error', __('Choose a module to restore.'));
            }

            $this->registryLog->restoreModule($alias);

            return redirect()
                ->route('module-management.module-settings')
                ->with('success', __('Module :alias restored from the log.', ['alias' => $alias]));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('module-management.module-settings')
                ->with('error', $e->getMessage());
        }
    }
}
