@extends('layouts.app')

@section('title', __('Roles Management') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Roles Management') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Module: :m / Page: :p', ['m' => 'user_settings', 'p' => 'roles_management']) }}</p>
        </div>
        <button
            type="button"
            class="admin-btn-primary shrink-0"
            onclick="window.dispatchEvent(new CustomEvent('roles-management-open-add'))"
        >
            {{ __('Add role') }}
        </button>
    </div>
@endsection

@section('content')
    <div
        data-roles-management-root
        x-data='rolesManagementPage(@json($rmInitial))'
        x-on:roles-management-open-add.window="openAdd()"
        x-on:click="handleActionClick($event)"
        class="space-y-0"
    >
        <table id="roles-management-table" class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-gray-700">
                    <th class="px-4 py-3 font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Slug') }}</th>
                    <th class="w-28 px-4 py-3 font-semibold">{{ __('Users') }}</th>
                    <th class="w-40 px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($roles as $role)
                    @php
                        $isBuiltIn = in_array($role->slug, [
                            \App\Models\Role::SLUG_SUPERADMIN,
                            \App\Models\Role::SLUG_ADMIN,
                            \App\Models\Role::SLUG_USER,
                        ], true);
                        $canManageRow = ! ($role->isSuperadmin() && ! auth()->user()->isSuperadmin());
                        $canDeleteRole = $canManageRow && ! $isBuiltIn && $role->users_count === 0;
                        $rolePayload = [
                            'id' => $role->id,
                            'slug' => $role->slug,
                            'name' => $role->name,
                            'slug_readonly' => $isBuiltIn,
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $role->name }}</td>
                        <td class="px-4 py-3 font-mono text-sm text-gray-700">{{ $role->slug }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($role->users_count) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManageRow)
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        data-roles-management-edit
                                        data-role-payload='@json($rolePayload)'
                                        class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                    >
                                        {{ __('Edit') }}
                                    </button>
                                    @if ($canDeleteRole)
                                        <form
                                            method="post"
                                            action="{{ route('page.user_settings.roles_management.destroy', $role) }}"
                                            class="inline"
                                            data-roles-management-delete="1"
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
                                    @else
                                        <button
                                            type="button"
                                            disabled
                                            class="inline-flex cursor-not-allowed rounded-lg border border-red-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-red-400 opacity-70"
                                            title="{{ $isBuiltIn ? __('Built-in roles cannot be deleted.') : __('Reassign all users before deleting this role.') }}"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-gray-400">{{ __('Restricted') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-500">{{ __('No roles yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <template x-teleport="body">
            <div
                x-show="editOpen && editRole"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-role-title"
                x-on:keydown.escape.window="closeEdit()"
                x-on:click.self="closeEdit()"
            >
                <div
                    class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl outline-none"
                    x-on:click.stop=""
                    role="document"
                >
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="edit-role-title" class="text-lg font-bold text-gray-900">{{ __('Edit role') }}</h2>
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

                            <div>
                                <label for="edit-role-slug" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Slug') }}</label>
                                <input
                                    id="edit-role-slug"
                                    name="slug"
                                    type="text"
                                    x-model="editRole.slug"
                                    x-bind:readonly="editRole.slug_readonly"
                                    required
                                    autocomplete="off"
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 font-mono text-sm text-gray-900 outline-none read-only:bg-slate-50 read-only:text-gray-600"
                                />
                                <p class="mt-2 text-xs text-gray-500" x-show="editRole.slug_readonly">{{ __('Built-in role slugs cannot be changed.') }}</p>
                            </div>

                            <div>
                                <label for="edit-role-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Display name') }}</label>
                                <input
                                    id="edit-role-name"
                                    name="name"
                                    type="text"
                                    x-model="editRole.name"
                                    required
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                />
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
                x-show="addOpen && addRole"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-role-title"
                x-on:keydown.escape.window="closeAdd()"
                x-on:click.self="closeAdd()"
            >
                <div
                    class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl outline-none"
                    x-on:click.stop=""
                    role="document"
                >
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="add-role-title" class="text-lg font-bold text-gray-900">{{ __('Add role') }}</h2>
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
                        action="{{ route('page.user_settings.roles_management.store') }}"
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

                            <div>
                                <label for="add-role-slug" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Slug') }}</label>
                                <input
                                    id="add-role-slug"
                                    name="slug"
                                    type="text"
                                    x-model="addRole.slug"
                                    required
                                    autocomplete="off"
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 font-mono text-sm text-gray-900 outline-none"
                                />
                                <p class="mt-2 text-xs text-gray-500">{{ __('Lowercase letters, numbers, and underscores only.') }}</p>
                            </div>

                            <div>
                                <label for="add-role-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Display name') }}</label>
                                <input
                                    id="add-role-name"
                                    name="name"
                                    type="text"
                                    x-model="addRole.name"
                                    required
                                    class="block w-full min-h-11 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none"
                                />
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
                                {{ __('Create role') }}
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
            const form = e.target.closest('form[data-roles-management-delete]');
            if (!form || !form.closest('[data-roles-management-root]')) {
                return;
            }
            e.preventDefault();
            if (typeof window.sys_dialog2 !== 'function') {
                if (window.confirm(@json(__('Delete this role permanently?')))) {
                    form.submit();
                }
                return;
            }
            const result = await window.sys_dialog2({
                title: @json(__('Delete role?')),
                text: @json(__('This cannot be undone. Delete this role permanently?')),
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
            const el = document.querySelector('#roles-management-table');
            if (!el) {
                return;
            }
            window.initDataTable('#roles-management-table', {
                order: [[0, 'asc']],
                pageLength: 25,
            });
        });
    </script>
@endpush
