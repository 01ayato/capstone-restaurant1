@extends('layouts.app')

@section('title', __('Module settings') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Module settings') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('Define modules available in the system. The registry file is :path — active rows are rewritten from the database on save; :marker lines are kept as archive only (not restorable).', ['path' => 'storage/logs/sys_module.log', 'marker' => '#deleted']) }}</p>
@endsection

@section('content')
    <div
        class="space-y-4"
        x-data="{ addOpen: {{ ($errors->any() && ! $editingModule && ! $deletingModule) ? 'true' : 'false' }}, restoreOpen: false }"
        x-on:keydown.escape.window="addOpen = false; restoreOpen = false"
    >
        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn-primary"
                x-on:click="addOpen = true"
            >
                {{ __('Add module') }}
            </button>
            @if ($restorableModules->isNotEmpty())
                <button
                    type="button"
                    class="rounded-lg border border-[#204865]/20 bg-[#204865]/10 px-4 py-2.5 text-sm font-semibold text-[#16364D] shadow-sm transition hover:bg-[#204865]/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#204865]"
                    x-on:click="restoreOpen = true"
                >
                    {{ __('Restore from log') }} ({{ $restorableModules->count() }})
                </button>
            @endif
        </div>

        <div class="admin-table-shell">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-gray-700">
                            <th class="w-10 px-3 py-3 font-semibold" scope="col">
                                <span class="sr-only">{{ __('Reorder') }}</span>
                            </th>
                            <th class="px-4 py-3 font-semibold">{{ __('Module name') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Module alias') }}</th>
                            <th class="w-24 px-4 py-3 font-semibold">{{ __('Seq #') }}</th>
                            <th class="w-36 px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th class="w-44 px-4 py-3 font-semibold">{{ __('Sidebar') }}</th>
                            <th class="w-28 px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($modules as $module)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-3 py-3 text-gray-300">
                                    <span class="inline-flex cursor-grab select-none text-lg leading-none" aria-hidden="true">⋮⋮</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $module->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $module->alias }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $module->sequence }}</td>
                                <td class="px-4 py-3">
                                    @if ($module->is_active)
                                        <span class="inline-flex flex-col gap-1">
                                            <span
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-default rounded-full bg-emerald-500 transition"
                                                role="img"
                                                aria-label="{{ __('Active') }}"
                                            >
                                                <span
                                                    class="pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition translate-x-5"
                                                ></span>
                                            </span>
                                            <span class="text-xs font-medium text-emerald-700">{{ __('Active') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex flex-col gap-1">
                                            <span
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-default rounded-full bg-gray-300 transition"
                                                role="img"
                                                aria-label="{{ __('Inactive') }}"
                                            >
                                                <span
                                                    class="pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition"
                                                ></span>
                                            </span>
                                            <span class="text-xs font-medium text-gray-500">{{ __('Inactive') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <form
                                        method="post"
                                        action="{{ route('module-management.module-settings.sidebar', $module) }}"
                                        class="flex max-w-44 flex-col gap-1"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="sidebar_expand_children" id="sidebar-expand-{{ $module->id }}" value="{{ $module->sidebar_expand_children ? '1' : '0' }}">
                                        <label class="inline-flex cursor-pointer items-center gap-2">
                                            <span class="text-xs text-gray-500">{{ __('Direct') }}</span>
                                            <input
                                                type="checkbox"
                                                class="peer relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-gray-300 transition before:absolute before:top-0.5 before:left-0.5 before:size-5 before:rounded-full before:bg-white before:shadow before:transition before:content-[''] checked:bg-emerald-500 checked:before:translate-x-5"
                                                @checked($module->sidebar_expand_children)
                                                aria-label="{{ __('Expand pages under this module in the sidebar') }}"
                                                onchange="document.getElementById('sidebar-expand-{{ $module->id }}').value = this.checked ? '1' : '0'; this.form.requestSubmit();"
                                            >
                                            <span class="text-xs text-gray-500">{{ __('Tree') }}</span>
                                        </label>
                                        <span class="text-[10px] leading-tight text-gray-400">{{ __('Direct opens the module home. Tree lists its pages below.') }}</span>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a
                                            href="{{ route('module-management.module-settings', ['edit' => $module->id]) }}"
                                            class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                        >
                                            {{ __('Edit') }}
                                        </a>
                                        <a
                                            href="{{ route('module-management.module-settings', ['delete_module' => $module->id]) }}"
                                            class="inline-flex rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                        >
                                            {{ __('Delete') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                    {{ __('No modules yet. Use “Add module” to create one.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add module modal --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D2232]/50 p-4"
            x-show="addOpen"
            x-transition
            x-cloak
            role="dialog"
            aria-modal="true"
            aria-labelledby="add-module-title"
            x-on:click="addOpen = false"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                x-on:click.stop=""
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h2 id="add-module-title" class="text-lg font-bold text-gray-900">{{ __('Add module') }}</h2>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                        x-on:click="addOpen = false"
                    >
                        <span class="sr-only">{{ __('Close') }}</span>
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    method="post"
                    action="{{ route('module-management.module-settings.store') }}"
                    class="space-y-6"
                    x-data="{
                        aliasTouched: @json((bool) (old('alias') || $errors->has('alias'))),
                        slugify(n) {
                            let s = (n || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                            s = s.replace(/_+/g, '_');
                            if (!s.length) return '';
                            if (!/^[a-z]/.test(s)) s = 'm_' + s.replace(/^_+/, '');
                            return s;
                        },
                        syncFromName(e) {
                            if (this.aliasTouched) return;
                            const el = this.$refs.aliasInput;
                            if (el) el.value = this.slugify(e.target.value);
                        },
                        markAliasTouched() { this.aliasTouched = true; }
                    }"
                >
                    @csrf

                    <div>
                        <label for="module-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                            {{ __('Module name') }}
                        </label>
                        <input
                            id="module-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            placeholder="{{ __('e.g. My Profile') }}"
                            x-on:input="syncFromName($event)"
                            style="border: 2px solid {{ $errors->has('name') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('name') ? '#fef2f2' : '#fff' }};"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                        />
                        @error('name')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="module-alias" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                            {{ __('Module alias') }}
                        </label>
                        <input
                            id="module-alias"
                            x-ref="aliasInput"
                            name="alias"
                            type="text"
                            value="{{ old('alias') }}"
                            required
                            pattern="[a-z][a-z0-9_]*"
                            autocomplete="off"
                            placeholder="profile_management"
                            x-on:input="markAliasTouched()"
                            style="border: 2px solid {{ $errors->has('alias') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('alias') ? '#fef2f2' : '#fff' }};"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 font-mono text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                        />
                        <p class="mt-2 text-xs text-gray-500">{{ __('Lowercase letters, numbers, and underscores. Must start with a letter. Filled automatically from the name until you edit it; must be unique in the database.') }}</p>
                        @error('alias')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="module-sequence" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                            {{ __('Seq #') }} <span class="font-normal normal-case text-gray-500">({{ __('optional') }})</span>
                        </label>
                        <input
                            id="module-sequence"
                            name="sequence"
                            type="number"
                            min="0"
                            max="99999"
                            value="{{ old('sequence', $nextSequence) }}"
                            style="border: 2px solid {{ $errors->has('sequence') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('sequence') ? '#fef2f2' : '#fff' }};"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                        />
                        <p class="mt-2 text-xs text-gray-500">{{ __('Auto-calculated from the latest module ID. You can change it manually.') }}</p>
                        @error('sequence')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3.5 shadow-sm transition hover:border-gray-200 focus-within:border-[#204865] focus-within:ring-2 focus-within:ring-[#0D2232]/10"
                    >
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            id="module-active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            class="size-5 shrink-0 rounded border border-gray-200 bg-white text-gray-900 accent-zinc-900 transition focus:ring-2 focus:ring-[#0D2232]/15 focus:ring-offset-2"
                            @checked(old('is_active', '1') !== '0')
                        />
                        <label for="module-active" class="cursor-pointer text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            x-on:click="addOpen = false"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0D2232]"
                        >
                            {{ __('Save module') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($restorableModules->isNotEmpty())
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D2232]/50 p-4"
                x-show="restoreOpen"
                x-transition
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-labelledby="restore-modules-title"
                x-on:click="restoreOpen = false"
            >
                <div
                    class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                    x-on:click.stop=""
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 id="restore-modules-title" class="text-lg font-bold text-gray-900">{{ __('Restore from log') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Active log lines not yet in the database (e.g. copied from another PC). :marker lines are archive-only and cannot be restored here.', ['marker' => '#deleted']) }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                            x-on:click="restoreOpen = false"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-gray-600">
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Module name') }}</th>
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Module alias') }}</th>
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Seq #') }}</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($restorableModules as $archived)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $archived['name'] }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $archived['alias'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $archived['sequence'] }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <form method="post" action="{{ route('module-management.module-settings.restore-from-log') }}">
                                                @csrf
                                                <input type="hidden" name="alias" value="{{ $archived['alias'] }}">
                                                <button
                                                    type="submit"
                                                    class="inline-flex rounded-lg border border-[#204865]/20 bg-[#204865]/10 px-3 py-1.5 text-xs font-semibold text-[#16364D] shadow-sm transition hover:bg-[#204865]/15"
                                                >
                                                    {{ __('Restore') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            x-on:click="restoreOpen = false"
                        >
                            {{ __('Close') }}
                        </button>
                        <form method="post" action="{{ route('module-management.module-settings.restore-from-log') }}">
                            @csrf
                            <input type="hidden" name="restore_all" value="1">
                            <button
                                type="submit"
                                class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                            >
                                {{ __('Restore all') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($editingModule)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-module-title"
            >
                <a
                    href="{{ route('module-management.module-settings') }}"
                    class="absolute inset-0 bg-[#0D2232]/50"
                    aria-label="{{ __('Close dialog') }}"
                ></a>
                <div
                    class="relative z-10 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h2 id="edit-module-title" class="text-lg font-bold text-gray-900">{{ __('Edit module') }}</h2>
                        <a
                            href="{{ route('module-management.module-settings') }}"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    </div>

                    <form method="post" action="{{ route('module-management.module-settings.update', $editingModule) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="edit-module-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                {{ __('Module name') }}
                            </label>
                            <input
                                id="edit-module-name"
                                name="name"
                                type="text"
                                value="{{ old('name', $editingModule->name) }}"
                                required
                                placeholder="{{ __('e.g. My Profile') }}"
                                style="border: 2px solid {{ $errors->has('name') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('name') ? '#fef2f2' : '#fff' }};"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                            />
                            @error('name')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit-module-alias" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                {{ __('Module alias') }}
                            </label>
                            <input
                                id="edit-module-alias"
                                name="alias"
                                type="text"
                                value="{{ old('alias', $editingModule->alias) }}"
                                required
                                pattern="[a-z][a-z0-9_]*"
                                autocomplete="off"
                                placeholder="profile_management"
                                style="border: 2px solid {{ $errors->has('alias') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('alias') ? '#fef2f2' : '#fff' }};"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 font-mono text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                            />
                            <p class="mt-2 text-xs text-gray-500">{{ __('Changing the alias renames the module folder under modules/ and refreshes child page routes. Must be unique among modules.') }}</p>
                            @error('alias')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit-module-sequence" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                {{ __('Seq #') }} <span class="font-normal normal-case text-gray-500">({{ __('optional') }})</span>
                            </label>
                            <input
                                id="edit-module-sequence"
                                name="sequence"
                                type="number"
                                min="0"
                                max="99999"
                                value="{{ old('sequence', $editingModule->sequence) }}"
                                style="border: 2px solid {{ $errors->has('sequence') ? '#f87171' : '#d4d4d8' }}; background: {{ $errors->has('sequence') ? '#fef2f2' : '#fff' }};"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#204865] focus:ring-2 focus:ring-[#0D2232]/10"
                            />
                            @error('sequence')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3.5 shadow-sm transition hover:border-gray-200 focus-within:border-[#204865] focus-within:ring-2 focus-within:ring-[#0D2232]/10"
                        >
                            <input type="hidden" name="is_active" value="0" />
                            <input
                                id="edit-module-active"
                                name="is_active"
                                type="checkbox"
                                value="1"
                                class="size-5 shrink-0 rounded border border-gray-200 bg-white text-gray-900 accent-zinc-900 transition focus:ring-2 focus:ring-[#0D2232]/15 focus:ring-offset-2"
                                @checked(old('is_active', $editingModule->is_active ? '1' : '0') !== '0')
                            />
                            <label for="edit-module-active" class="cursor-pointer text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                        </div>

                        <div
                            class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3.5 shadow-sm"
                        >
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Sidebar') }}</span>
                            <input type="hidden" name="sidebar_expand_children" value="0" />
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    name="sidebar_expand_children"
                                    value="1"
                                    class="size-5 shrink-0 rounded border border-gray-200 bg-white accent-zinc-900"
                                    @checked(old('sidebar_expand_children', $editingModule->sidebar_expand_children ? '1' : '0') !== '0')
                                />
                                <span class="text-sm font-medium text-gray-700">{{ __('Expand pages in sidebar (tree view)') }}</span>
                            </label>
                            <p class="text-[10px] leading-tight text-gray-400">{{ __('When off, the module row links straight to the module home; when on, it opens a list of pages.') }}</p>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                            <a
                                href="{{ route('module-management.module-settings') }}"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <button
                                type="submit"
                                class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0D2232]"
                            >
                                {{ __('Update module') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($deletingModule)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="delete-module-title"
            >
                <a
                    href="{{ route('module-management.module-settings') }}"
                    class="absolute inset-0 bg-[#0D2232]/50"
                    aria-label="{{ __('Close dialog') }}"
                ></a>
                <div
                    class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h2 id="delete-module-title" class="text-lg font-bold text-red-900">{{ __('Delete module') }}</h2>
                        <a
                            href="{{ route('module-management.module-settings') }}"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    </div>

                    <p class="mb-3 text-sm font-medium text-gray-700">{{ __('This will permanently delete the module and every page under it, then wipe related files and registry logs. Order: pages first, then the module. This cannot be undone.') }}</p>

                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                        <p class="mb-2 font-semibold">{{ __('Pages that will be removed first (:count)', ['count' => $deletingModule->pages->count()]) }}</p>
                        @if ($deletingModule->pages->isEmpty())
                            <p class="text-amber-900">{{ __('No pages are registered for this module.') }}</p>
                        @else
                            <ul class="max-h-40 list-inside list-disc space-y-1 overflow-y-auto font-medium">
                                @foreach ($deletingModule->pages as $pg)
                                    <li>{{ $pg->page_name }} <span class="font-mono text-xs text-amber-900/90">({{ $pg->page_alias }})</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <ul class="mb-4 list-inside list-disc space-y-1.5 rounded-lg border border-red-100 bg-red-50/80 px-4 py-3 text-sm text-red-950">
                        <li>{{ __('All database rows for those pages, then the module row') }}</li>
                        <li><span class="font-mono text-xs">modules/{{ $deletingModule->alias }}/pages/*/ </span> {{ __('(each page folder)') }}</li>
                        <li><span class="font-mono text-xs">modules/{{ $deletingModule->alias }}/</span> {{ __('(module code, web.php, module route)') }}</li>
                        <li><span class="font-mono text-xs">resources/views/modules/{{ $deletingModule->alias }}/</span> {{ __('(all module & page views)') }}</li>
                        @if (\Illuminate\Support\Facades\Route::has('module.'.$deletingModule->alias.'.index'))
                            <li>{{ __('Named route: :route', ['route' => 'module.'.$deletingModule->alias.'.index']) }}</li>
                        @endif
                        <li>{{ __('Registry files: :pages and :modules will be rewritten.', ['pages' => 'storage/logs/sysql_pages.log', 'modules' => 'storage/logs/sys_module.log']) }}</li>
                    </ul>

                    <form method="post" action="{{ route('module-management.module-settings.destroy', $deletingModule) }}" class="space-y-4">
                        @csrf
                        @method('DELETE')

                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                name="delete_confirmed"
                                value="1"
                                class="mt-0.5 size-4 shrink-0 rounded border border-gray-200 text-gray-900 accent-red-600"
                                @checked(old('delete_confirmed'))
                                required
                            />
                            <span>{{ __('I understand this will wipe the module, all listed pages, and the files above.') }}</span>
                        </label>
                        @error('delete_confirmed')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                            <a
                                href="{{ route('module-management.module-settings') }}"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <button
                                type="submit"
                                class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800"
                            >
                                {{ __('Delete module and pages permanently') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
