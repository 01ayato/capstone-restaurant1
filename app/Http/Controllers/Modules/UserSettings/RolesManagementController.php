<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\UserSettings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RolesManagementController extends Controller
{
    public function index(): View
    {
        $this->authorizeStaff();

        $roles = Role::query()
            ->withCount('users')
            ->orderBy('id')
            ->get();

        $editDraft = $this->buildEditDraftForView();
        $createDraft = $this->buildCreateDraftForView();

        return view('modules.user_settings.pages.roles_management.index', [
            'roles' => $roles,
            'editDraft' => $editDraft,
            'createDraft' => $createDraft,
            'rmInitial' => [
                'editOpen' => $editDraft !== null,
                'editRole' => $editDraft,
                'addOpen' => $createDraft !== null,
                'addRole' => $createDraft,
                'updateBase' => url('modules/user_settings/roles_management'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeStaff();

        $request->merge([
            'slug' => strtolower(trim((string) $request->input('slug', ''))),
        ]);

        $validator = Validator::make($request->all(), [
            'slug' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_]+$/', 'unique:roles,slug'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('page.user_settings.roles_management.index', ['create' => 1])
                ->withErrors($validator)
                ->withInput();
        }

        Role::create($validator->validated());

        return redirect()
            ->route('page.user_settings.roles_management.index')
            ->with('success', __('Role created.'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeStaff();
        $this->assertCanManageRoleRow($role);

        $builtIn = $this->isBuiltInRole($role);

        if ($builtIn) {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:100'],
            ]);
        } else {
            $request->merge([
                'slug' => strtolower(trim((string) $request->input('slug', ''))),
            ]);
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:100'],
                'slug' => [
                    'required',
                    'string',
                    'max:32',
                    'regex:/^[a-z0-9_]+$/',
                    Rule::unique('roles', 'slug')->ignore($role->id),
                ],
            ]);
        }

        if ($validator->fails()) {
            return redirect()
                ->route('page.user_settings.roles_management.index', ['edit' => $role->id])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        if ($builtIn) {
            $role->update(['name' => $validated['name']]);
        } else {
            $role->update($validated);
        }

        return redirect()
            ->route('page.user_settings.roles_management.index')
            ->with('success', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeStaff();
        $this->assertCanManageRoleRow($role);

        if ($this->isBuiltInRole($role)) {
            return redirect()
                ->route('page.user_settings.roles_management.index')
                ->with('error', __('Built-in roles cannot be deleted.'));
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('page.user_settings.roles_management.index')
                ->with('error', __('Reassign all users before deleting this role.'));
        }

        $role->delete();

        return redirect()
            ->route('page.user_settings.roles_management.index')
            ->with('success', __('Role deleted.'));
    }

    private function authorizeStaff(): void
    {
        $actor = Auth::user();
        if ($actor === null || (! $actor->isSuperadmin() && ! $actor->isAdmin())) {
            abort(403);
        }
    }

    private function isBuiltInRole(Role $role): bool
    {
        return in_array($role->slug, [
            Role::SLUG_SUPERADMIN,
            Role::SLUG_ADMIN,
            Role::SLUG_USER,
        ], true);
    }

    private function actorCanManageRoleRow(Role $role): bool
    {
        if ($role->isSuperadmin() && ! Auth::user()?->isSuperadmin()) {
            return false;
        }

        return true;
    }

    private function assertCanManageRoleRow(Role $role): void
    {
        if (! $this->actorCanManageRoleRow($role)) {
            abort(403);
        }
    }

    /** @return array<string, mixed>|null */
    private function buildEditDraftForView(): ?array
    {
        if (! request()->filled('edit')) {
            return null;
        }

        $errors = session('errors');
        if ($errors === null || ! $errors->any()) {
            return null;
        }

        $role = Role::query()->find((int) request('edit'));
        if ($role === null || ! $this->actorCanManageRoleRow($role)) {
            return null;
        }

        $builtIn = $this->isBuiltInRole($role);

        return [
            'id' => $role->id,
            'slug' => (string) old('slug', $role->slug),
            'name' => (string) old('name', $role->name),
            'slug_readonly' => $builtIn,
        ];
    }

    /** @return array<string, mixed>|null */
    private function buildCreateDraftForView(): ?array
    {
        if (! request()->has('create')) {
            return null;
        }

        $errors = session('errors');
        if ($errors === null || ! $errors->any()) {
            return null;
        }

        return [
            'slug' => (string) old('slug', ''),
            'name' => (string) old('name', ''),
        ];
    }
}
