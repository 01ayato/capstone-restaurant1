@extends('layouts.app')

@section('title', __('Page settings') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Page settings') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('Pages belong to a module. Files live under modules/{module}/pages/{page}/. The registry file is :path — active rows are rewritten on save; :marker lines are archive-only (not restorable).', ['path' => 'storage/logs/sysql_pages.log', 'marker' => '#deleted']) }}</p>
@endsection

@section('content')
    <div
        class="space-y-4"
        x-data="{
            addOpen: {{ ($errors->any() && ! $editingPage && ! $deletingPage) ? 'true' : 'false' }},
            restoreOpen: false,
            addModuleId: '{{ addslashes((string) old('module_id', '')) }}',
            aliasTouched: {{ (old('page_alias') || $errors->has('page_alias')) ? 'true' : 'false' }},
            slugify(n) {
                let s = (n || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                s = s.replace(/_+/g, '_');
                if (!s.length) return '';
                if (!/^[a-z]/.test(s)) s = 'p_' + s.replace(/^_+/, '');
                return s;
            },
            markAliasTouched() { this.aliasTouched = true; },
            openAdd() {
                this.addModuleId = '';
                this.aliasTouched = false;
                this.addOpen = true;
            },
            openAddForModule(moduleId) {
                this.addModuleId = String(moduleId);
                this.aliasTouched = false;
                this.addOpen = true;
            },
            onModuleChange() {
                if (this.aliasTouched) return;
                const nameEl = this.$refs.pageNameInput;
                const aliasEl = this.$refs.pageAliasInput;
                if (nameEl && aliasEl && nameEl.value) aliasEl.value = this.slugify(nameEl.value);
            },
        }"
        x-on:keydown.escape.window="addOpen = false; restoreOpen = false"
    >
        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn-primary"
                x-on:click="openAdd()"
                @if ($modules->isEmpty()) disabled @endif
            >
                {{ __('Add page') }}
            </button>
            @if ($restorablePages->isNotEmpty())
                <button
                    type="button"
                    class="rounded-lg border border-[#204865]/20 bg-[#204865]/10 px-4 py-2.5 text-sm font-semibold text-[#16364D] shadow-sm transition hover:bg-[#204865]/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#204865]"
                    x-on:click="restoreOpen = true"
                >
                    {{ __('Restore from log') }} ({{ $restorablePages->count() }})
                </button>
            @endif
        </div>

        @if ($modules->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Create at least one active module in Module settings before adding pages.') }}
            </div>
        @endif

        <div class="admin-table-shell">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-gray-700">
                            <th class="w-10 px-3 py-3 font-semibold" scope="col">
                                <span class="sr-only">{{ __('Reorder') }}</span>
                            </th>
                            <th class="px-4 py-3 font-semibold">{{ __('Page name') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Page alias') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Module') }}</th>
                            <th class="w-24 px-4 py-3 font-semibold">{{ __('Seq #') }}</th>
                            <th class="min-w-32 px-4 py-3 font-semibold">{{ __('Icon') }}</th>
                            <th class="w-36 px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th class="w-28 px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($moduleGroups as $moduleGroup)
                            <tr class="bg-slate-100">
                                <td class="px-3 py-2.5"></td>
                                <td colspan="6" class="px-4 py-2.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <svg class="size-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-900">{{ $moduleGroup->name }}</span>
                                        <span class="font-mono text-xs text-gray-500">{{ $moduleGroup->alias }}</span>
                                        @unless ($moduleGroup->is_active)
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-gray-600">{{ __('Inactive') }}</span>
                                        @endunless
                                        <span class="text-xs text-gray-500">{{ trans_choice('{0}No pages|{1}1 page|[2,*]:count pages', $moduleGroup->pages->count(), ['count' => $moduleGroup->pages->count()]) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    @if ($moduleGroup->is_active)
                                        <button
                                            type="button"
                                            class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:bg-slate-50 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0D2232]"
                                            x-on:click="openAddForModule({{ $moduleGroup->id }})"
                                            title="{{ __('Add page to :module', ['module' => $moduleGroup->name]) }}"
                                            aria-label="{{ __('Add page to :module', ['module' => $moduleGroup->name]) }}"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @forelse ($moduleGroup->pages as $page)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-3 py-3 text-gray-300">
                                        <span class="inline-flex cursor-grab select-none text-lg leading-none" aria-hidden="true">⋮⋮</span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        <span class="inline-flex items-center gap-2">
                                            @if ($page->icon)
                                                <i class="{{ e($page->icon) }} text-gray-500" aria-hidden="true"></i>
                                            @endif
                                            <span>{{ $page->page_name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $page->page_alias }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $moduleGroup->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $page->sequence_number }}</td>
                                    <td class="max-w-40 truncate px-4 py-3 font-mono text-xs text-gray-500" title="{{ $page->icon }}">{{ $page->icon ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($page->is_active)
                                            <span class="text-xs font-medium text-emerald-700">{{ __('Active') }}</span>
                                        @else
                                            <span class="text-xs font-medium text-gray-500">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route('module-management.page-settings', ['edit' => $page->id]) }}"
                                                class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                {{ __('Edit') }}
                                            </a>
                                            <a
                                                href="{{ route('module-management.page-settings', ['delete_page' => $page->id]) }}"
                                                class="inline-flex rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                                            >
                                                {{ __('Delete') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-3"></td>
                                    <td colspan="7" class="px-4 py-3 text-xs italic text-gray-500">
                                        {{ __('No pages registered for this module yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                    {{ __('No modules yet. Create one in Module settings, then add pages here.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D2232]/50 p-4"
            x-show="addOpen"
            x-transition
            x-cloak
            role="dialog"
            aria-modal="true"
            aria-labelledby="add-page-title"
            x-on:click="addOpen = false"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                x-on:click.stop=""
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h2 id="add-page-title" class="text-lg font-bold text-gray-900">{{ __('Add page') }}</h2>
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
                    action="{{ route('module-management.page-settings.store') }}"
                    class="space-y-5"
                >
                    @csrf

                    <div>
                        <label for="page-module" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Module') }}</label>
                        <select
                            id="page-module"
                            name="module_id"
                            required
                            x-model="addModuleId"
                            x-on:change="onModuleChange()"
                            class="block w-full min-h-11 rounded-lg px-3 py-2.5 text-sm text-gray-900 outline-none"
                            style="border: 2px solid {{ $errors->has('module_id') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                        >
                            <option value="">{{ __('Select module') }}</option>
                            @foreach ($modules as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->alias }})</option>
                            @endforeach
                        </select>
                        @error('module_id')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="page-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Page name') }}</label>
                        <input
                            id="page-name"
                            x-ref="pageNameInput"
                            name="page_name"
                            type="text"
                            value="{{ old('page_name') }}"
                            required
                            placeholder="{{ __('Enter page name') }}"
                            x-on:input="!aliasTouched && $refs.pageAliasInput && ($refs.pageAliasInput.value = slugify($event.target.value))"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400"
                            style="border: 2px solid {{ $errors->has('page_name') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                        />
                        @error('page_name')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="page-alias" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Page alias') }}</label>
                        <input
                            id="page-alias"
                            x-ref="pageAliasInput"
                            name="page_alias"
                            type="text"
                            value="{{ old('page_alias') }}"
                            required
                            pattern="[a-z][a-z0-9_]*"
                            placeholder="my_leave"
                            x-on:input="markAliasTouched()"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 font-mono text-sm text-gray-900 outline-none transition placeholder:text-gray-400"
                            style="border: 2px solid {{ $errors->has('page_alias') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                        />
                        <p class="mt-2 text-xs text-gray-500">{{ __('Lowercase, unique within the selected module. Filled automatically from the page name until you edit it.') }}</p>
                        @error('page_alias')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="page-seq" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Seq #') }}</label>
                        <input
                            id="page-seq"
                            name="sequence_number"
                            type="number"
                            min="0"
                            max="99999"
                            value="{{ old('sequence_number', $nextSequence) }}"
                            class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition"
                            style="border: 2px solid {{ $errors->has('sequence_number') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                        />
                        @error('sequence_number')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Icon') }}</span>
                        <div
                            class="flex min-h-11 flex-wrap items-center gap-3 rounded-lg px-3 py-2.5"
                            style="border: 2px solid {{ $errors->has('icon') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                        >
                            <span
                                id="page-icon-preview"
                                class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-slate-50 text-lg text-gray-700"
                                aria-hidden="true"
                            >
                                @if (old('icon'))
                                    <i class="{{ e(old('icon')) }}" aria-hidden="true"></i>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </span>
                            <input type="hidden" name="icon" id="page-icon" value="{{ old('icon') }}" />
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                onclick="window.chooseIconForField('page-icon', 'page-icon-preview')"
                            >
                                {{ __('Choose icon…') }}
                            </button>
                        </div>
                        @error('icon')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            id="page-active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            class="size-5 shrink-0 rounded border border-gray-200 bg-white accent-zinc-900"
                            @checked(old('is_active', '1') !== '0')
                        />
                        <label for="page-active" class="cursor-pointer text-sm font-medium text-gray-700">{{ __('Active') }}</label>
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
                            class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                        >
                            {{ __('Save page') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($restorablePages->isNotEmpty())
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D2232]/50 p-4"
                x-show="restoreOpen"
                x-transition
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-labelledby="restore-pages-title"
                x-on:click="restoreOpen = false"
            >
                <div
                    class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                    x-on:click.stop=""
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 id="restore-pages-title" class="text-lg font-bold text-gray-900">{{ __('Restore from log') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Active log lines not yet in the database. Restore the parent module first if needed. :marker lines are archive-only.', ['marker' => '#deleted']) }}</p>
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
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Page name') }}</th>
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Page alias') }}</th>
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Module') }}</th>
                                    <th class="px-4 py-2.5 font-semibold">{{ __('Seq #') }}</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($restorablePages as $archived)
                                    <tr @class(['bg-amber-50' => ! $archived['module_exists']])>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $archived['page_name'] }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $archived['page_alias'] }}</td>
                                        <td class="px-4 py-3">
                                            @if ($archived['module_alias'] !== '')
                                                <span class="font-mono text-xs text-gray-600">{{ $archived['module_alias'] }}</span>
                                                @unless ($archived['module_exists'])
                                                    <span class="mt-1 block text-xs text-amber-800">{{ __('Module missing — restore it first') }}</span>
                                                @endunless
                                            @else
                                                <span class="text-xs text-amber-800">{{ __('Unknown module (log id: :id). Add `, module_alias` before @ in the log line.', ['id' => $archived['log_module_id'] ?? '?']) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $archived['sequence_number'] }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($archived['module_exists'])
                                                <form method="post" action="{{ route('module-management.page-settings.restore-from-log') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_alias" value="{{ $archived['module_alias'] }}">
                                                    <input type="hidden" name="page_alias" value="{{ $archived['page_alias'] }}">
                                                    <button
                                                        type="submit"
                                                        class="inline-flex rounded-lg border border-[#204865]/20 bg-[#204865]/10 px-3 py-1.5 text-xs font-semibold text-[#16364D] shadow-sm transition hover:bg-[#204865]/15"
                                                    >
                                                        {{ __('Restore') }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $restorableReadyCount = $restorablePages->filter(fn (array $row): bool => $row['module_exists'])->count();
                    @endphp
                    <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            x-on:click="restoreOpen = false"
                        >
                            {{ __('Close') }}
                        </button>
                        @if ($restorableReadyCount > 0)
                            <form method="post" action="{{ route('module-management.page-settings.restore-from-log') }}">
                                @csrf
                                <input type="hidden" name="restore_all" value="1">
                                <button
                                    type="submit"
                                    class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                                >
                                    {{ __('Restore all ready') }} ({{ $restorableReadyCount }})
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($editingPage)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-page-title"
            >
                <a
                    href="{{ route('module-management.page-settings') }}"
                    class="absolute inset-0 bg-[#0D2232]/50"
                    aria-label="{{ __('Close dialog') }}"
                ></a>
                <div
                    class="relative z-10 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h2 id="edit-page-title" class="text-lg font-bold text-gray-900">{{ __('Edit page') }}</h2>
                        <a
                            href="{{ route('module-management.page-settings') }}"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    </div>

                    <form method="post" action="{{ route('module-management.page-settings.update', $editingPage) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="edit-page-module" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Module') }}</label>
                            <select
                                id="edit-page-module"
                                name="module_id"
                                required
                                class="block w-full min-h-11 rounded-lg px-3 py-2.5 text-sm text-gray-900 outline-none"
                                style="border: 2px solid {{ $errors->has('module_id') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                            >
                                <option value="">{{ __('Select module') }}</option>
                                @foreach ($modules as $m)
                                    <option value="{{ $m->id }}" @selected((string) old('module_id', $editingPage->module_id) === (string) $m->id)>{{ $m->name }} ({{ $m->alias }})</option>
                                @endforeach
                            </select>
                            @error('module_id')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit-page-name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Page name') }}</label>
                            <input
                                id="edit-page-name"
                                name="page_name"
                                type="text"
                                value="{{ old('page_name', $editingPage->page_name) }}"
                                required
                                placeholder="{{ __('Enter page name') }}"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400"
                                style="border: 2px solid {{ $errors->has('page_name') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                            />
                            @error('page_name')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit-page-alias" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Page alias') }}</label>
                            <input
                                id="edit-page-alias"
                                name="page_alias"
                                type="text"
                                value="{{ old('page_alias', $editingPage->page_alias) }}"
                                required
                                pattern="[a-z][a-z0-9_]*"
                                placeholder="my_leave"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 font-mono text-sm text-gray-900 outline-none transition placeholder:text-gray-400"
                                style="border: 2px solid {{ $errors->has('page_alias') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                            />
                            <p class="mt-2 text-xs text-gray-500">{{ __('Lowercase, unique within the selected module. Changing the alias moves the page folder under modules/.') }}</p>
                            @error('page_alias')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit-page-seq" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Seq #') }}</label>
                            <input
                                id="edit-page-seq"
                                name="sequence_number"
                                type="number"
                                min="0"
                                max="99999"
                                value="{{ old('sequence_number', $editingPage->sequence_number) }}"
                                class="block w-full min-h-11 rounded-lg px-4 py-3 text-sm text-gray-900 outline-none transition"
                                style="border: 2px solid {{ $errors->has('sequence_number') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                            />
                            @error('sequence_number')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Icon') }}</span>
                            <div
                                class="flex min-h-11 flex-wrap items-center gap-3 rounded-lg px-3 py-2.5"
                                style="border: 2px solid {{ $errors->has('icon') ? '#f87171' : '#d4d4d8' }}; background: #fff;"
                            >
                                <span
                                    id="edit-page-icon-preview"
                                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-slate-50 text-lg text-gray-700"
                                    aria-hidden="true"
                                >
                                    @php
                                        $editIcon = old('icon', $editingPage->icon);
                                    @endphp
                                    @if ($editIcon)
                                        <i class="{{ e($editIcon) }}" aria-hidden="true"></i>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </span>
                                <input type="hidden" name="icon" id="edit-page-icon" value="{{ $editIcon }}" />
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                    onclick="window.chooseIconForField('edit-page-icon', 'edit-page-icon-preview')"
                                >
                                    {{ __('Choose icon…') }}
                                </button>
                            </div>
                            @error('icon')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3">
                            <input type="hidden" name="is_active" value="0" />
                            <input
                                id="edit-page-active"
                                name="is_active"
                                type="checkbox"
                                value="1"
                                class="size-5 shrink-0 rounded border border-gray-200 bg-white accent-zinc-900"
                                @checked(old('is_active', $editingPage->is_active ? '1' : '0') !== '0')
                            />
                            <label for="edit-page-active" class="cursor-pointer text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                            <a
                                href="{{ route('module-management.page-settings') }}"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <button
                                type="submit"
                                class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]"
                            >
                                {{ __('Update page') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($deletingPage)
            @php
                $delModAlias = $deletingPage->module?->alias;
                $delRouteName = $delModAlias ? 'page.'.$delModAlias.'.'.$deletingPage->page_alias.'.index' : null;
            @endphp
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="delete-page-title"
            >
                <a
                    href="{{ route('module-management.page-settings') }}"
                    class="absolute inset-0 bg-[#0D2232]/50"
                    aria-label="{{ __('Close dialog') }}"
                ></a>
                <div
                    class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h2 id="delete-page-title" class="text-lg font-bold text-red-900">{{ __('Delete page') }}</h2>
                        <a
                            href="{{ route('module-management.page-settings') }}"
                            class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    </div>

                    <p class="mb-4 text-sm font-medium text-gray-700">{{ __('This will permanently remove the page and wipe related generated assets. This cannot be undone.') }}</p>

                    <ul class="mb-4 list-inside list-disc space-y-1.5 rounded-lg border border-red-100 bg-red-50/80 px-4 py-3 text-sm text-red-950">
                        <li>{{ __('Database row for :name', ['name' => $deletingPage->page_name]) }}</li>
                        @if ($delModAlias)
                            <li><span class="font-mono text-xs">modules/{{ $delModAlias }}/pages/{{ $deletingPage->page_alias }}/</span> {{ __('(code & routes)') }}</li>
                            <li><span class="font-mono text-xs">resources/views/modules/{{ $delModAlias }}/pages/{{ $deletingPage->page_alias }}/</span> {{ __('(Blade views)') }}</li>
                            @if ($delRouteName && \Illuminate\Support\Facades\Route::has($delRouteName))
                                <li>{{ __('Named route: :route', ['route' => $delRouteName]) }}</li>
                            @endif
                        @else
                            <li>{{ __('Module folder paths (module missing from database)') }}</li>
                        @endif
                        <li>{{ __('Registry file :path will be rewritten without this page.', ['path' => 'storage/logs/sysql_pages.log']) }}</li>
                    </ul>

                    <form method="post" action="{{ route('module-management.page-settings.destroy', $deletingPage) }}" class="space-y-4">
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
                            <span>{{ __('I understand this will permanently delete this page and remove the files listed above.') }}</span>
                        </label>
                        @error('delete_confirmed')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                            <a
                                href="{{ route('module-management.page-settings') }}"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <button
                                type="submit"
                                class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800"
                            >
                                {{ __('Delete page permanently') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    @php
        $faIconPickerI18n = [
            'title' => __('Choose icon'),
            'search' => __('Search icons'),
            'clear' => __('Clear icon'),
            'cancel' => __('Cancel'),
            'apply' => __('Use icon'),
            'current' => __('Current'),
            'notInList' => __('This icon is not in the gallery. Pick one below or clear.'),
        ];
    @endphp
    @push('scripts')
        <script>
            window.__faIconPickerI18n = @json($faIconPickerI18n);
        </script>
    @endpush
@endsection
