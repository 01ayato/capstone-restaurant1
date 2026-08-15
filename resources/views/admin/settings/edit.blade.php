@extends('layouts.app')

@section('title', 'Restaurant Settings')

@section('page-header')
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Brand settings</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Restaurant Information</h1>
    <p class="mt-1 text-sm text-gray-500">Keep the details shown to customers accurate and up to date.</p>
@endsection

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="post" class="admin-card max-w-3xl space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Restaurant Name</label>
            <input name="name" value="{{ old('name', $settings->name) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">{{ old('description', $settings->description) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">Address</label>
            <input name="address" value="{{ old('address', $settings->address) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Phone</label>
                <input name="phone" value="{{ old('phone', $settings->phone) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Email</label>
                <input name="email" type="email" value="{{ old('email', $settings->email) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Delivery Fee (₱)</label>
                <input name="delivery_fee" type="number" step="0.01" min="0" value="{{ old('delivery_fee', $settings->delivery_fee) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Opening Hours</label>
                <input name="opening_hours" value="{{ old('opening_hours', $settings->opening_hours) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="border-t border-gray-100 pt-5">
            <button type="submit" class="admin-btn-primary">
                <x-admin-icon name="store" size="size-4" />
                Save Settings
            </button>
        </div>
    </form>
@endsection
