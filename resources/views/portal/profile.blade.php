@extends('layouts.portal')

@section('title', 'Profile — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-xl">
            <h1 class="font-display text-3xl font-bold text-gray-900">My Profile</h1>
            <p class="mt-2 text-gray-500">Manage your account information.</p>

            <form action="{{ route('profile.update') }}" method="post" class="mt-8 space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_4px_12px_rgba(15,23,42,.05)] sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">First Name</label>
                        <input id="first_name" name="first_name" type="text" required value="{{ old('first_name', $user->first_name) }}" class="sg-input">
                    </div>
                    <div>
                        <label for="last_name" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Last Name</label>
                        <input id="last_name" name="last_name" type="text" required value="{{ old('last_name', $user->last_name) }}" class="sg-input">
                    </div>
                </div>

                <div>
                    <label for="username" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" class="sg-input">
                </div>

                <div>
                    <label for="email" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Email Address</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}" class="sg-input">
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Phone Number</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" class="sg-input">
                </div>

                <div class="border-t border-gray-200 pt-5">
                    <p class="mb-4 text-sm text-gray-500">Leave password fields blank to keep your current password.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">New Password</label>
                            <input id="password" name="password" type="password" class="sg-input">
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Confirm Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="sg-input">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-orange-500 py-3 text-sm font-bold text-white transition hover:bg-orange-400">
                    Save Changes
                </button>
            </form>
        </div>
    </section>
@endsection
