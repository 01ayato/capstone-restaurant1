@extends('layouts.app')

@section('title', __('Module permissions') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('Module permissions') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Module: :m / Page: :p', ['m' => 'authorization', 'p' => 'module_permissions']) }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="admin-card">
            <p class="text-sm text-gray-700">
                {{ __('Only checked roles may see this module in the sidebar and open its pages. Unchecked means no access for that role. If no role is checked for a module, only a superadmin can use it until you assign at least one role.') }}
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
            action="{{ route('page.authorization.module_permissions.update') }}"
            class="admin-table-shell"
        >
            @csrf

            <div class="overflow-x-auto rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-gray-700">
                            <th class="sticky left-0 z-10 min-w-[12rem] bg-slate-50 px-4 py-3 font-semibold shadow-[4px_0_8px_-4px_rgba(0,0,0,0.3)]">
                                {{ __('Module') }}
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
                        @forelse ($modules as $module)
                            @php
                                $selectedIds = collect(
                                    old('permissions.' . $module->id, $module->roles->pluck('id')->all())
                                )
                                    ->map(fn ($id) => (int) $id)
                                    ->unique()
                                    ->values()
                                    ->all();
                            @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="sticky left-0 z-10 border-r border-gray-100 bg-white px-4 py-3 font-medium text-gray-900 shadow-[4px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <span class="block">{{ $module->name }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-gray-500">{{ $module->alias }}</span>
                                </td>
                                @foreach ($roles as $role)
                                    <td class="px-3 py-3 text-center align-middle">
                                        <label class="inline-flex cursor-pointer items-center justify-center">
                                            <input
                                                type="checkbox"
                                                name="permissions[{{ $module->id }}][]"
                                                value="{{ $role->id }}"
                                                class="size-4 rounded border-gray-200 text-gray-900 focus:ring-[#204865]"
                                                @checked(in_array((int) $role->id, $selectedIds, true))
                                            />
                                            <span class="sr-only">{{ $module->name }} — {{ $role->name }}</span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1 + $roles->count(), 2) }}" class="px-4 py-12 text-center text-sm text-gray-500">
                                    {{ __('No active modules found. Add modules in Module management.') }}
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
