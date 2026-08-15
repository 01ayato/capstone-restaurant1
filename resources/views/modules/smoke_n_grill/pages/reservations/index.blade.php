@extends('layouts.app')

@section('title', 'Reservations — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Reservations</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('Module: :m / Page: :p', ['m' => 'smoke_n_grill', 'p' => 'reservations']) }}</p>
@endsection

@section('content')
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-600">{{ __('This page was generated when the entry was added in Page settings.') }}</p>
    </div>
@endsection
