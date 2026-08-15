@extends('layouts.app')

@section('title', 'Accounts and Roles — ' . config('app.name', 'Laravel'))

@section('page-header')
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Accounts and Roles</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('Module: :alias', ['alias' => 'accounts_and_roles']) }}</p>
@endsection

@section('content')
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-600">{{ __('This page was generated when the module was added. Replace this view with your feature UI.') }}</p>
    </div>
@endsection
