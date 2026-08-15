@extends('layouts.app')

@section('title', __('Users Lists') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Users Lists') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Module: :m / Page: :p', ['m' => 'user_settings', 'p' => 'users_lists']) }}</p>
        </div>
        <button
            type="button"
            class="admin-btn-primary shrink-0"
            onclick="window.dispatchEvent(new CustomEvent('users-lists-open-add'))"
        >
            {{ __('Add user') }}
        </button>
    </div>
@endsection

@section('content')
    <div
        data-users-lists-root
        x-data='userAccountsPage(@json($uaInitial))'
        x-on:users-lists-open-add.window="openAdd()"
        x-on:click="handleActionClick($event)"
        class="space-y-0"
    >
        <table id="users-lists-table" class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-gray-700">
                    <th class="px-4 py-3 font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Email') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Role') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Phone') }}</th>
                    <th class="w-40 px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($users as $user)
                    @php
                        $canManageRow = ! ($user->isSuperadmin() && ! auth()->user()->isSuperadmin());
                        $userPayload = [
                            'id' => $user->id,
                            'first_name' => $user->first_name ?? '',
                            'middle_name' => $user->middle_name ?? '',
                            'last_name' => $user->last_name ?? '',
                            'suffix' => $user->suffix ?? '',
                            'email' => $user->email,
                            'phone' => $user->phone ?? '',
                            'date_of_birth' => $user->date_of_birth?->format('Y-m-d') ?? '',
                            'gender' => $user->gender ?? '',
                            'role_id' => (string) $user->role_id,
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $user->role?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManageRow)
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        data-user-accounts-edit
                                        data-user-payload='@json($userPayload)'
                                        class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                    >
                                        {{ __('Edit') }}
                                    </button>
                                    @if ($user->id === auth()->id())
                                        <button
                                            type="button"
                                            disabled
                                            class="inline-flex cursor-not-allowed rounded-lg border border-red-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-red-400 opacity-70"
                                            title="{{ __('You cannot delete your own account.') }}"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    @else
                                        <form
                                            method="post"
                                            action="{{ route('page.user_settings.users_lists.destroy', $user) }}"
                                            class="inline"
                                            data-users-lists-delete="1"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                            >
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-gray-400">{{ __('Restricted') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">{{ __('No users yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <template x-teleport="body">
            <div
                x-show="editOpen && editUser"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-user-title"
                x-on:keydown.escape.window="closeEdit()"
                x-on:click.self="closeEdit()"
            >
                <div
                    class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl outline-none"
                    x-on:click.stop=""
                    role="document"
                >
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="edit-user-title" class="text-lg font-bold text-gray-900">{{ __('Edit user') }}</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                            x-on:click="closeEdit()"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form
                        method="post"
                        x-bind:action="editFormAction()"
                        class="flex min-h-0 flex-1 flex-col"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-y-contain px-6 py-4">
                            @if ($errors->any() && $editDraft)
                                <div
                                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                                    role="alert"
                                >
                                    <p class="font-semibold text-red-950">{{ __('Please correct the following:') }}</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-first_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('First name') }}</label>
                                    <input
                                        id="edit-first_name"
                                        name="first_name"
                                        type="text"
                                        x-model="editUser.first_name"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="edit-middle_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Middle name') }}</label>
                                    <input
                                        id="edit-middle_name"
                                        name="middle_name"
                                        type="text"
                                        x-model="editUser.middle_name"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-last_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Last name') }}</label>
                                    <input
                                        id="edit-last_name"
                                        name="last_name"
                                        type="text"
                                        x-model="editUser.last_name"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="edit-suffix" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Suffix') }}</label>
                                    <input
                                        id="edit-suffix"
                                        name="suffix"
                                        type="text"
                                        x-model="editUser.suffix"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="edit-email" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Email') }}</label>
                                <input
                                    id="edit-email"
                                    name="email"
                                    type="email"
                                    x-model="editUser.email"
                                    required
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-phone" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Phone') }}</label>
                                    <input
                                        id="edit-phone"
                                        name="phone"
                                        type="text"
                                        x-model="editUser.phone"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="edit-date_of_birth" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Date of birth') }}</label>
                                    <input
                                        id="edit-date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                        x-model="editUser.date_of_birth"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-gender" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Gender') }}</label>
                                    <select
                                        id="edit-gender"
                                        name="gender"
                                        x-model="editUser.gender"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none"
                                    >
                                        <option value="">{{ __('Not specified') }}</option>
                                        <option value="female">{{ __('Female') }}</option>
                                        <option value="male">{{ __('Male') }}</option>
                                        <option value="non_binary">{{ __('Non-binary') }}</option>
                                        <option value="prefer_not_to_say">{{ __('Prefer not to say') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit-role_id" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Role') }}</label>
                                    <select
                                        id="edit-role_id"
                                        name="role_id"
                                        x-model="editUser.role_id"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none"
                                    >
                                        <option value="">{{ __('Select role') }}</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-password" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('New password') }}</label>
                                    <input
                                        id="edit-password"
                                        name="password"
                                        type="password"
                                        autocomplete="new-password"
                                        x-model="editUser.password"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                    <p class="mt-2 text-xs text-gray-500">{{ __('Leave blank to keep the current password.') }}</p>
                                </div>
                                <div>
                                    <label for="edit-password_confirmation" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Confirm password') }}</label>
                                    <input
                                        id="edit-password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        x-model="editUser.password_confirmation"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-4">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                x-on:click="closeEdit()"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                            >
                                {{ __('Save changes') }}
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="addOpen && addUser"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-user-title"
                x-on:keydown.escape.window="closeAdd()"
                x-on:click.self="closeAdd()"
            >
                <div
                    class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl outline-none"
                    x-on:click.stop=""
                    role="document"
                >
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="add-user-title" class="text-lg font-bold text-gray-900">{{ __('Add user') }}</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                            x-on:click="closeAdd()"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form
                        method="post"
                        action="{{ route('page.user_settings.users_lists.store') }}"
                        class="flex min-h-0 flex-1 flex-col"
                    >
                        @csrf

                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-y-contain px-6 py-4">
                            @if ($errors->any() && $createDraft !== null)
                                <div
                                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                                    role="alert"
                                >
                                    <p class="font-semibold text-red-950">{{ __('Please correct the following:') }}</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="add-first_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('First name') }}</label>
                                    <input
                                        id="add-first_name"
                                        name="first_name"
                                        type="text"
                                        x-model="addUser.first_name"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="add-middle_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Middle name') }}</label>
                                    <input
                                        id="add-middle_name"
                                        name="middle_name"
                                        type="text"
                                        x-model="addUser.middle_name"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="add-last_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Last name') }}</label>
                                    <input
                                        id="add-last_name"
                                        name="last_name"
                                        type="text"
                                        x-model="addUser.last_name"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="add-suffix" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Suffix') }}</label>
                                    <input
                                        id="add-suffix"
                                        name="suffix"
                                        type="text"
                                        x-model="addUser.suffix"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="add-email" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Email') }}</label>
                                <input
                                    id="add-email"
                                    name="email"
                                    type="email"
                                    x-model="addUser.email"
                                    required
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="add-phone" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Phone') }}</label>
                                    <input
                                        id="add-phone"
                                        name="phone"
                                        type="text"
                                        x-model="addUser.phone"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="add-date_of_birth" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Date of birth') }}</label>
                                    <input
                                        id="add-date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                        x-model="addUser.date_of_birth"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="add-gender" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Gender') }}</label>
                                    <select
                                        id="add-gender"
                                        name="gender"
                                        x-model="addUser.gender"
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none"
                                    >
                                        <option value="">{{ __('Not specified') }}</option>
                                        <option value="female">{{ __('Female') }}</option>
                                        <option value="male">{{ __('Male') }}</option>
                                        <option value="non_binary">{{ __('Non-binary') }}</option>
                                        <option value="prefer_not_to_say">{{ __('Prefer not to say') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="add-role_id" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Role') }}</label>
                                    <select
                                        id="add-role_id"
                                        name="role_id"
                                        x-model="addUser.role_id"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none"
                                    >
                                        <option value="">{{ __('Select role') }}</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="add-password" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Password') }}</label>
                                    <input
                                        id="add-password"
                                        name="password"
                                        type="password"
                                        autocomplete="new-password"
                                        x-model="addUser.password"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                                <div>
                                    <label for="add-password_confirmation" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Confirm password') }}</label>
                                    <input
                                        id="add-password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        x-model="addUser.password_confirmation"
                                        required
                                        class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-4">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                x-on:click="closeAdd()"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                            >
                                {{ __('Create user') }}
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('submit', async function (e) {
            const form = e.target.closest('form[data-users-lists-delete]');
            if (!form || !form.closest('[data-users-lists-root]')) {
                return;
            }
            e.preventDefault();
            if (typeof window.sys_dialog2 !== 'function') {
                if (window.confirm(@json(__('Delete this user permanently?')))) {
                    form.submit();
                }
                return;
            }
            const result = await window.sys_dialog2({
                title: @json(__('Delete user?')),
                text: @json(__('This cannot be undone. Delete this user permanently?')),
                confirmButtonText: @json(__('Delete')),
                cancelButtonText: @json(__('Cancel')),
                width: '28rem',
                focusCancel: true,
            });
            if (result.isConfirmed) {
                form.submit();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initDataTable !== 'function') {
                return;
            }
            const el = document.querySelector('#users-lists-table');
            if (!el) {
                return;
            }
            window.initDataTable('#users-lists-table', {
                order: [[0, 'asc']],
                pageLength: 25,
            });
        });
    </script>
@endpush
