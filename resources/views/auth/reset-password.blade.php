@extends('layouts.guest')

@section('title', 'New password — ' . config('app.name', 'Laravel'))

@section('content')
    <div class="relative isolate flex min-h-dvh flex-col justify-center px-4 py-12 sm:px-6">
        @include('auth.partials.waves')

        <div class="relative z-10 mx-auto w-full max-w-md">
            <div class="rounded-3xl bg-white p-10 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-900/5">
                <div class="flex justify-center">
                    <div
                        class="flex size-12 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-md"
                        aria-hidden="true"
                    >
                        <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z" />
                        </svg>
                    </div>
                </div>

                <h1 class="mt-6 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">
                    Choose a new password
                </h1>
                <p class="mt-2 text-center text-sm text-zinc-500">
                    Set a new password for {{ $email }}
                </p>

                @if (session('info'))
                    <div
                        class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                        role="status"
                    >
                        {{ session('info') }}
                    </div>
                @endif

                <form action="{{ route('password.update') }}" method="post" class="mt-8 space-y-6">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}" />
                    <input type="hidden" name="email" value="{{ $email }}" />

                    <div x-data="{ showPassword: false }">
                        <label for="password" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                            New password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                autofocus
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 pr-10 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-indigo-600 transition">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ showPassword: false }">
                        <label for="password_confirmation" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                            Confirm password
                        </label>
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 pr-10 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-indigo-600 transition">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    @error('email')
                        <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror

                    <button
                        type="submit"
                        class="flex w-full justify-center rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900"
                    >
                        Update password
                    </button>
                </form>

                <p class="mt-10 text-center text-sm text-zinc-500">
                    <a href="{{ route('home') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                        Back to sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection
