@extends('layouts.guest-dark')

@section('title', 'Log in — Smoke n\' Grill')

@section('content')
    <div class="relative flex min-h-dvh flex-col justify-center px-4 py-12 sm:px-6">
        <div class="relative z-10 mx-auto w-full max-w-md">
            <div class="rounded-3xl border border-white/10 bg-zinc-900/80 p-10 shadow-2xl shadow-orange-500/5 backdrop-blur-xl ring-1 ring-white/5">
                <div class="flex justify-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 text-2xl text-zinc-950 shadow-lg shadow-orange-500/30" aria-hidden="true">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>
                </div>

                <h1 class="mt-6 text-center font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Welcome back
                </h1>
                <p class="mt-2 text-center text-sm text-zinc-400">
                    Sign in to your Smoke n' Grill account
                </p>

                <form action="{{ route('login.perform') }}" method="post" class="mt-10 space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Email Address</label>
                        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus class="sg-input">
                        @error('email')<p class="mt-2 text-sm font-medium text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ showPassword: false }">
                        <div class="mb-2 flex items-baseline justify-between gap-2">
                            <label for="password" class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-orange-400 hover:text-orange-300">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="sg-input pr-10">
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-orange-400 transition">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')<p class="mt-2 text-sm font-medium text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-400">
                        <input type="checkbox" name="remember" value="1" class="rounded border-zinc-600 bg-zinc-800 text-orange-500 focus:ring-orange-500/30">
                        Remember me
                    </label>

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-bold text-zinc-950 shadow-lg shadow-orange-500/25 transition hover:from-orange-400 hover:to-amber-400">
                        Sign in
                    </button>
                </form>

                <p class="mt-10 text-center text-sm text-zinc-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-orange-400 hover:text-orange-300">Register</a>
                </p>
                <p class="mt-4 text-center">
                    <a href="{{ route('landing') }}" class="text-xs text-zinc-600 hover:text-zinc-400">&larr; Back to website</a>
                </p>
            </div>
        </div>
    </div>
@endsection
