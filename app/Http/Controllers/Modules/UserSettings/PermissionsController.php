<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\UserSettings;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Page;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PermissionsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeStaff();

        $roles = Role::query()->orderBy('id')->get();

        $modules = Module::query()
            ->where('is_active', true)
            ->with([
                'pages' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sequence_number')
                    ->orderBy('id'),
                'roles:id',
                'pages.roles:id',
            ])
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $selectedRoleId = (int) $request->query(
            'role',
            old('role_id', $roles->firstWhere('slug', '!=', Role::SLUG_SUPERADMIN)?->id ?? $roles->first()?->id ?? 0),
        );

        if ($selectedRoleId > 0 && ! $roles->contains('id', $selectedRoleId)) {
            $selectedRoleId = (int) ($roles->first()?->id ?? 0);
        }

        $matrix = [];
        foreach ($roles as $role) {
            $moduleIds = [];
            $pageIds = [];
            foreach ($modules as $module) {
                if ($module->roles->contains('id', $role->id)) {
                    $moduleIds[] = $module->id;
                }
                foreach ($module->pages as $page) {
                    if ($page->roles->contains('id', $role->id)) {
                        $pageIds[] = $page->id;
                    }
                }
            }
            $matrix[$role->id] = [
                'modules' => $moduleIds,
                'pages' => $pageIds,
            ];
        }

        return view('modules.user_settings.pages.permissions.index', [
            'roles' => $roles,
            'modules' => $modules,
            'selectedRoleId' => $selectedRoleId,
            'permissionsInitial' => [
                'selectedRoleId' => $selectedRoleId,
                'matrix' => $matrix,
                'superadminSlug' => Role::SLUG_SUPERADMIN,
                'roles' => $roles->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_superadmin' => $role->isSuperadmin(),
                ])->values()->all(),
                'modules' => $modules->map(fn (Module $module) => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'alias' => $module->alias,
                    'pages' => $module->pages->map(fn (Page $page) => [
                        'id' => $page->id,
                        'name' => $page->page_name,
                        'alias' => $page->page_alias,
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeStaff();

        $moduleIds = Module::query()->where('is_active', true)->pluck('id')->all();
        $pageIds = Page::query()
            ->where('is_active', true)
            ->whereHas('module', fn ($q) => $q->where('is_active', true))
            ->pluck('id')
            ->all();

        $validator = Validator::make($request->all(), [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['integer', Rule::in($moduleIds)],
            'pages' => ['nullable', 'array'],
            'pages.*' => ['integer', Rule::in($pageIds)],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('page.user_settings.permissions.index', [
                    'role' => $request->input('role_id'),
                ])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $role = Role::query()->findOrFail((int) $validated['role_id']);

        if ($role->isSuperadmin()) {
            return redirect()
                ->route('page.user_settings.permissions.index', ['role' => $role->id])
                ->with('info', __('Superadmin always has full access. Visibility for this role cannot be changed.'));
        }

        $selectedModules = array_values(array_unique(array_map('intval', $validated['modules'] ?? [])));
        $selectedPages = array_values(array_unique(array_map('intval', $validated['pages'] ?? [])));

        // Pages require their parent module; auto-include parents of checked pages.
        $pageModuleMap = Page::query()
            ->whereIn('id', $selectedPages)
            ->pluck('module_id', 'id');

        foreach ($pageModuleMap as $moduleId) {
            $selectedModules[] = (int) $moduleId;
        }
        $selectedModules = array_values(array_unique(array_intersect($selectedModules, $moduleIds)));
        $selectedPages = array_values(array_unique(array_intersect($selectedPages, $pageIds)));

        DB::transaction(function () use ($role, $selectedModules, $selectedPages): void {
            $role->modules()->sync($selectedModules);
            $role->pages()->sync($selectedPages);
        });

        return redirect()
            ->route('page.user_settings.permissions.index', ['role' => $role->id])
            ->with('success', __('Visibility for :role saved.', ['role' => $role->name]));
    }

    private function authorizeStaff(): void
    {
        $actor = Auth::user();
        if ($actor === null || (! $actor->isSuperadmin() && ! $actor->isAdmin())) {
            abort(403);
        }
    }
}
