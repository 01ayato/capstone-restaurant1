@extends('layouts.app')

@section('title', 'Customers')

@section('page-header')
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Guests</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Customers</h1>
    <p class="mt-1 text-sm text-gray-500">Review customer details and order activity.</p>
@endsection

@section('content')
    <div class="admin-table-shell overflow-x-auto">
        <table class="min-w-[640px] divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Phone</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Orders</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $customer->orders->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
