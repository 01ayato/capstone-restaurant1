<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\UserSettings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UsersListsController extends Controller
{
    public function index(): View
    {
        $this->authorizeStaff();

        $users = User::query()
            ->with('role')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();

        $editDraft = $this->buildEditDraftForView();
        $createDraft = $this->buildCreateDraftForView();

        return view('modules.user_settings.pages.users_lists.index', [
            'users' => $users,
            'roles' => $this->manageableRoles(),
            'editDraft' => $editDraft,
            'createDraft' => $createDraft,
            'uaInitial' => [
                'editOpen' => $editDraft !== null,
                'editUser' => $editDraft,
                'addOpen' => $createDraft !== null,
                'addUser' => $createDraft,
                'updateBase' => url('modules/user_settings/users_lists'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeStaff();

        $request->merge([
            'phone' => filled($request->input('phone')) ? trim((string) $request->input('phone')) : null,
            'date_of_birth' => filled($request->input('date_of_birth')) ? $request->input('date_of_birth') : null,
            'middle_name' => filled($request->input('middle_name')) ? trim((string) $request->input('middle_name')) : null,
            'suffix' => filled($request->input('suffix')) ? trim((string) $request->input('suffix')) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'after:1900-01-01', 'before:today'],
            'gender' => ['nullable', 'in:female,male,non_binary,prefer_not_to_say'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->max(30)
                    ->letters()
                    ->numbers()
                    ->mixedCase()
                    ->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('page.user_settings.users_lists.index', ['create' => 1])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $role = Role::query()->findOrFail((int) $validated['role_id']);
        try {
            $this->assertCanAssignRole($role);
        } catch (ValidationException $e) {
            return redirect()
                ->route('page.user_settings.users_lists.index', ['create' => 1])
                ->withErrors($e->errors())
                ->withInput();
        }

        User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'password' => $validated['password'],
            'role_id' => $role->id,
        ]);

        return redirect()
            ->route('page.user_settings.users_lists.index')
            ->with('success', __('User account created.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeStaff();
        $this->assertCanManageTargetUser($user);

        $request->merge([
            'phone' => filled($request->input('phone')) ? trim((string) $request->input('phone')) : null,
            'date_of_birth' => filled($request->input('date_of_birth')) ? $request->input('date_of_birth') : null,
            'middle_name' => filled($request->input('middle_name')) ? trim((string) $request->input('middle_name')) : null,
            'suffix' => filled($request->input('suffix')) ? trim((string) $request->input('suffix')) : null,
        ]);

        if (! $request->filled('password')) {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'after:1900-01-01', 'before:today'],
            'gender' => ['nullable', 'in:female,male,non_binary,prefer_not_to_say'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8)
                    ->max(30)
                    ->letters()
                    ->numbers()
                    ->mixedCase()
                    ->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('page.user_settings.users_lists.index', ['edit' => $user->id])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $newRole = Role::query()->findOrFail((int) $validated['role_id']);
        try {
            $this->assertCanAssignRole($newRole);
            $this->assertSuperadminRoleChangeSafe($user, $newRole);
        } catch (ValidationException $e) {
            return redirect()
                ->route('page.user_settings.users_lists.index', ['edit' => $user->id])
                ->withErrors($e->errors())
                ->withInput();
        }

        $payload = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'role_id' => $newRole->id,
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('page.user_settings.users_lists.index')
            ->with('success', __('User account updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeStaff();
        $this->assertCanManageTargetUser($user);

        if ($user->id === Auth::id()) {
            return redirect()
                ->route('page.user_settings.users_lists.index')
                ->with('error', __('You cannot delete your own account.'));
        }

        if ($user->isSuperadmin() && $this->superadminUserCount() <= 1) {
            return redirect()
                ->route('page.user_settings.users_lists.index')
                ->with('error', __('You cannot delete the only superadmin account.'));
        }

        $user->delete();

        return redirect()
            ->route('page.user_settings.users_lists.index')
            ->with('success', __('User account deleted.'));
    }

    private function authorizeStaff(): void
    {
        $actor = Auth::user();
        if ($actor === null || (! $actor->isSuperadmin() && ! $actor->isAdmin())) {
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

        $user = User::query()->find((int) request('edit'));
        if ($user === null || ! $this->actorCanManageTargetUser($user)) {
            return null;
        }

        return [
            'id' => $user->id,
            'first_name' => (string) old('first_name', $user->first_name ?? ''),
            'middle_name' => (string) (old('middle_name', $user->middle_name) ?? ''),
            'last_name' => (string) old('last_name', $user->last_name ?? ''),
            'suffix' => (string) (old('suffix', $user->suffix) ?? ''),
            'email' => (string) old('email', $user->email ?? ''),
            'phone' => (string) (old('phone', $user->phone) ?? ''),
            'date_of_birth' => (string) (old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) ?? ''),
            'gender' => (string) (old('gender', $user->gender) ?? ''),
            'role_id' => (string) old('role_id', (string) $user->role_id),
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
            'first_name' => (string) old('first_name', ''),
            'middle_name' => (string) old('middle_name', ''),
            'last_name' => (string) old('last_name', ''),
            'suffix' => (string) old('suffix', ''),
            'email' => (string) old('email', ''),
            'phone' => (string) old('phone', ''),
            'date_of_birth' => (string) old('date_of_birth', ''),
            'gender' => (string) old('gender', ''),
            'role_id' => (string) old('role_id', ''),
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    private function actorCanManageTargetUser(User $user): bool
    {
        if ($user->isSuperadmin() && ! Auth::user()?->isSuperadmin()) {
            return false;
        }

        return true;
    }

    /** @return Collection<int, Role> */
    private function manageableRoles(): Collection
    {
        $q = Role::query()->orderBy('id');

        if (Auth::user()?->isSuperadmin()) {
            return $q->get();
        }

        return $q->where('slug', '!=', Role::SLUG_SUPERADMIN)->get();
    }

    private function assertCanAssignRole(Role $role): void
    {
        if ($role->isSuperadmin() && ! Auth::user()?->isSuperadmin()) {
            throw ValidationException::withMessages([
                'role_id' => __('Only a superadmin can assign the superadmin role.'),
            ]);
        }
    }

    private function assertCanManageTargetUser(User $user): void
    {
        if ($user->isSuperadmin() && ! Auth::user()?->isSuperadmin()) {
            abort(403);
        }
    }

    private function assertSuperadminRoleChangeSafe(User $user, Role $newRole): void
    {
        if (! $user->isSuperadmin()) {
            return;
        }

        if ($newRole->isSuperadmin()) {
            return;
        }

        if ($this->superadminUserCount() <= 1) {
            throw ValidationException::withMessages([
                'role_id' => __('There must be at least one superadmin. Assign another superadmin before changing this account’s role.'),
            ]);
        }
    }

    private function superadminUserCount(): int
    {
        $superadminId = Role::query()->where('slug', Role::SLUG_SUPERADMIN)->value('id');
        if ($superadminId === null) {
            return 0;
        }

        return User::query()->where('role_id', $superadminId)->count();
    }
}
