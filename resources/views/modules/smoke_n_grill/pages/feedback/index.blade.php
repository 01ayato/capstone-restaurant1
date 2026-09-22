@extends('layouts.app')

@section('title', 'Feedback — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-xl font-bold tracking-tight text-zinc-900">Feedback</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Module: :m / Page: :p', ['m' => 'smoke_n_grill', 'p' => 'feedback']) }}</p>
@endsection

@section('content')
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-zinc-600">{{ __('This page was generated when the entry was added in Page settings.') }}</p>
    </div>
@endsection
