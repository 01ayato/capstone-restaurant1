@extends('layouts.guest')

@section('title', 'Forgot password — ' . config('app.name', 'Laravel'))

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
                    Reset password
                </h1>
                <p class="mt-2 text-center text-sm text-zinc-500">
                    Enter your email to reset your password
                </p>

                <div
                    class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                    role="status"
                >
                    Debug mode — no email is sent. You will go straight to the reset form.
                </div>

                <form action="{{ route('password.email') }}" method="post" class="mt-8 space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                            Email
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            placeholder="hello@example.com"
                            required
                            autofocus
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        />
                        @error('email')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="flex w-full justify-center rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900"
                    >
                        Continue
                    </button>
                </form>

                <p class="mt-10 text-center text-sm text-zinc-500">
                    Remember your password?
                    <a href="{{ route('home') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                        Back to sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection
