@extends('layouts.app')

@section('title', __('Dashboard') . ' — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-xl font-bold tracking-tight text-zinc-900">{{ __('Dashboard') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Signed in as :email', ['email' => auth()->user()->email]) }}</p>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-zinc-600">
                {{ __('Replace this card with your lending widgets. Other pages can use the same layout: create a Blade file that extends layouts.app and define the content section.') }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-zinc-900">{{ __('Livewire') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Interactive components without writing custom JavaScript.') }}</p>
            <div class="mt-4">
                <livewire:counter />
            </div>
        </div>
    </div>
@endsection
