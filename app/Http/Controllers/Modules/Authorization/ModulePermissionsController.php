<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Authorization;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModulePermissionsController extends Controller
{
    public function index(): View
    {
        $this->authorizeStaff();

        $modules = Module::query()
            ->where('is_active', true)
            ->with(['roles' => fn ($q) => $q->orderBy('id')])
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $roles = Role::query()->orderBy('id')->get();

        return view('modules.authorization.pages.module_permissions.index', [
            'modules' => $modules,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeStaff();

        $modules = Module::query()->where('is_active', true)->pluck('id')->all();

        $permissions = $request->input('permissions', []);
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $rules = [];
        foreach ($modules as $mid) {
            $rules['permissions.'.$mid] = ['nullable', 'array'];
            $rules['permissions.'.$mid.'.*'] = ['integer', 'distinct', 'exists:roles,id'];
        }

        $validator = Validator::make(['permissions' => $permissions], $rules);
        if ($validator->fails()) {
            return redirect()
                ->route('page.authorization.module_permissions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $clean = $validated['permissions'] ?? [];

        DB::transaction(function () use ($modules, $clean): void {
            foreach ($modules as $moduleId) {
                $raw = $clean[(string) $moduleId] ?? $clean[$moduleId] ?? [];
                if (! is_array($raw)) {
                    $raw = [];
                }
                $ids = array_values(array_unique(array_map('intval', $raw)));
                $module = Module::query()->find($moduleId);
                if ($module !== null) {
                    $module->roles()->sync($ids);
                }
            }
        });

        return redirect()
            ->route('page.authorization.module_permissions.index')
            ->with('success', __('Module visibility saved.'));
    }

    private function authorizeStaff(): void
    {
        $actor = Auth::user();
        if ($actor === null || (! $actor->isSuperadmin() && ! $actor->isAdmin())) {
            abort(403);
        }
    }
}
