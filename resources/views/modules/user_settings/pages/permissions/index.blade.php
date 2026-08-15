@extends('layouts.app')

@section('title', __('Permissions') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Permissions') }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Choose a role, then set which modules and pages that role can view.') }}
            </p>
        </div>
    </div>
@endsection

@section('content')
    <script type="application/json" id="permissions-initial">@json($permissionsInitial)</script>
    <div
        x-data="permissionsPage(JSON.parse(document.getElementById('permissions-initial')?.textContent || '{}'))"
        class="space-y-6"
    >
        <div class="admin-card">
            <p class="text-sm text-gray-700">
                {{ __('Checked = can view in the sidebar and open the page. Unchecked = hidden / blocked. Module access is required before a page can be opened.') }}
            </p>
            <p class="mt-2 text-sm font-medium text-gray-900">
                {{ __('Superadmin always has full access and cannot be restricted.') }}
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold text-red-950">{{ __('Please correct the following:') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-table-shell overflow-hidden">
            <div class="border-b border-gray-200 bg-slate-50 px-4 py-3 sm:px-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Role') }}</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="role in roles" :key="role.id">
                        <button
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            x-on:click="selectRole(role.id)"
                            x-bind:class="Number(selectedRoleId) === Number(role.id)
                                ? 'border-[#0D2232] bg-[#0D2232] text-white'
                                : 'border-gray-200 bg-white text-gray-700 hover:bg-slate-50'"
                        >
                            <span x-text="role.name"></span>
                            <span
                                class="ml-1 font-mono text-[10px] uppercase tracking-wide opacity-70"
                                x-text="role.slug"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>

            <form
                method="post"
                action="{{ route('page.user_settings.permissions.update') }}"
                class="space-y-0"
            >
                @csrf
                <input type="hidden" name="role_id" x-bind:value="selectedRoleId">

                <template x-for="moduleId in (current?.modules ?? [])" :key="'m-' + moduleId">
                    <input type="hidden" name="modules[]" x-bind:value="moduleId" x-bind:disabled="isSuperadminSelected">
                </template>
                <template x-for="pageId in (current?.pages ?? [])" :key="'p-' + pageId">
                    <input type="hidden" name="pages[]" x-bind:value="pageId" x-bind:disabled="isSuperadminSelected">
                </template>

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            <span x-text="selectedRole?.name ?? '{{ __('Select a role') }}'"></span>
                            <span class="font-normal text-gray-500">{{ __('visibility') }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500" x-show="isSuperadminSelected">
                            {{ __('This role always sees every module and page.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2" x-show="!isSuperadminSelected">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            x-on:click="selectAllForRole()"
                        >
                            {{ __('Allow all') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            x-on:click="clearAllForRole()"
                        >
                            {{ __('Deny all') }}
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    <template x-for="module in modules" :key="module.id">
                        <div class="px-4 py-4 sm:px-6">
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-1 size-4 rounded border-gray-300 text-[#0D2232] focus:ring-[#204865]"
                                    x-bind:checked="hasModule(module.id)"
                                    x-bind:disabled="isSuperadminSelected"
                                    x-on:change="toggleModule(module.id, $event.target.checked)"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gray-900" x-text="module.name"></span>
                                    <span class="mt-0.5 block font-mono text-xs text-gray-500" x-text="module.alias"></span>
                                    <span class="mt-1 inline-flex text-[11px] font-medium uppercase tracking-wide"
                                        x-bind:class="hasModule(module.id) ? 'text-emerald-700' : 'text-rose-700'"
                                        x-text="hasModule(module.id) ? '{{ __('Can view module') }}' : '{{ __('Cannot view module') }}'"
                                    ></span>
                                </span>
                            </label>

                            <div class="mt-3 space-y-2 border-l-2 border-gray-100 pl-4 sm:ml-7" x-show="(module.pages ?? []).length > 0">
                                <template x-for="page in module.pages" :key="page.id">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 size-4 rounded border-gray-300 text-[#0D2232] focus:ring-[#204865]"
                                            x-bind:checked="hasPage(page.id)"
                                            x-bind:disabled="isSuperadminSelected"
                                            x-on:change="togglePage(module.id, page.id, $event.target.checked)"
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900" x-text="page.name"></span>
                                                <span
                                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                                    x-bind:class="hasPage(page.id)
                                                        ? 'bg-emerald-50 text-emerald-800'
                                                        : 'bg-rose-50 text-rose-800'"
                                                    x-text="hasPage(page.id) ? '{{ __('Can view') }}' : '{{ __('Cannot view') }}'"
                                                ></span>
                                            </span>
                                            <span class="mt-0.5 block font-mono text-xs text-gray-500" x-text="page.alias"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="modules.length === 0">
                        <div class="px-4 py-12 text-center text-sm text-gray-500 sm:px-6">
                            {{ __('No active modules found. Add modules in Module management.') }}
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-4 py-4 sm:px-6">
                    <button
                        type="submit"
                        class="admin-btn-primary"
                        x-bind:disabled="isSuperadminSelected || !selectedRoleId"
                        x-bind:class="(isSuperadminSelected || !selectedRoleId) ? 'opacity-50 cursor-not-allowed' : ''"
                    >
                        {{ __('Save visibility') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
