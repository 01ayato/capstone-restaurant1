@extends('layouts.app')

@section('title', __('Page permissions') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Page permissions') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Module: :m / Page: :p', ['m' => 'authorization', 'p' => 'page_permissions']) }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="admin-card">
            <p class="text-sm text-gray-700">
                {{ __('Only checked roles may open this page (you must already pass module access). Unchecked means no access for that role. If no role is checked for a page, only a superadmin can open it until you assign at least one role.') }}
            </p>
            <p class="mt-2 text-sm font-medium text-gray-900">{{ __('Superadmin') }} — {{ __('always has access to everything.') }}</p>
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

        <form
            method="post"
            action="{{ route('page.authorization.page_permissions.update') }}"
            class="admin-table-shell"
        >
            @csrf

            <div class="overflow-x-auto rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-gray-700">
                            <th class="sticky left-0 z-10 min-w-[14rem] bg-[#16364D] px-4 py-3 font-semibold shadow-[4px_0_8px_-4px_rgba(0,0,0,0.3)]">
                                {{ __('Page') }}
                            </th>
                            @foreach ($roles as $role)
                                <th class="whitespace-nowrap px-3 py-3 text-center font-semibold">
                                    <span class="block max-w-[10rem] truncate" title="{{ $role->name }}">{{ $role->name }}</span>
                                    <span class="mt-0.5 block font-mono text-[10px] font-normal uppercase tracking-wide text-gray-300">{{ $role->slug }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($pages as $page)
                            @php
                                $selectedIds = collect(
                                    old('permissions.' . $page->id, $page->roles->pluck('id')->all())
                                )
                                    ->map(fn ($id) => (int) $id)
                                    ->unique()
                                    ->values()
                                    ->all();
                            @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="sticky left-0 z-10 border-r border-gray-100 bg-white px-4 py-3 font-medium text-gray-900 shadow-[4px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <span class="block">{{ $page->page_name }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-600">{{ $page->module?->name ?? '—' }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-gray-500">{{ $page->module?->alias ?? '?' }} / {{ $page->page_alias }}</span>
                                </td>
                                @foreach ($roles as $role)
                                    <td class="px-3 py-3 text-center align-middle">
                                        <label class="inline-flex cursor-pointer items-center justify-center">
                                            <input
                                                type="checkbox"
                                                name="permissions[{{ $page->id }}][]"
                                                value="{{ $role->id }}"
                                                class="size-4 rounded border-gray-200 text-gray-900 focus:ring-[#204865]"
                                                @checked(in_array((int) $role->id, $selectedIds, true))
                                            />
                                            <span class="sr-only">{{ $page->page_name }} — {{ $role->name }}</span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1 + $roles->count(), 2) }}" class="px-4 py-12 text-center text-sm text-gray-500">
                                    {{ __('No active pages found. Add pages in Module management.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-4 py-4 sm:px-6">
                <button
                    type="submit"
                    class="admin-btn-primary"
                >
                    {{ __('Save changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection
