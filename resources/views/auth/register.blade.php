@extends('layouts.guest-dark')

@section('title', 'Register — Smoke n\' Grill')

@section('content')
    <div class="relative flex min-h-dvh flex-col justify-center px-4 py-10 sm:px-6 sm:py-12">
        <div class="relative z-10 mx-auto w-full max-w-xl">
            <div class="rounded-3xl border border-white/10 bg-zinc-900/80 p-8 shadow-2xl shadow-orange-500/5 backdrop-blur-xl ring-1 ring-white/5 sm:p-10">
                <div class="flex justify-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 text-2xl text-zinc-950 shadow-lg shadow-orange-500/30" aria-hidden="true">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>
                </div>

                <h1 class="mt-6 text-center font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">Create Account</h1>
                <p class="mt-2 text-center text-sm text-zinc-400">Join Smoke n' Grill and start ordering</p>

                <form action="{{ route('register.perform') }}" method="post" class="mt-8 space-y-5">
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">First Name</label>
                            <input id="first_name" name="first_name" type="text" required autofocus value="{{ old('first_name') }}" class="sg-input">
                        </div>
                        <div>
                            <label for="last_name" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Last Name</label>
                            <input id="last_name" name="last_name" type="text" required value="{{ old('last_name') }}" class="sg-input">
                        </div>
                    </div>

                    <div>
                        <label for="username" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Username</label>
                        <input id="username" name="username" type="text" required value="{{ old('username') }}" class="sg-input">
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Email Address</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}" class="sg-input">
                    </div>

                    <div>
                        <label for="phone" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Phone Number</label>
                        <input id="phone" name="phone" type="tel" required placeholder="09xx xxx xxxx" value="{{ old('phone') }}" class="sg-input">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div x-data="{ showPassword: false }">
                            <label for="password" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Password</label>
                            <div class="relative">
                                <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required class="sg-input pr-10">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-orange-400 transition">
                                    <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                        <div x-data="{ showPassword: false }">
                            <label for="password_confirmation" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Confirm Password</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" required class="sg-input pr-10">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-orange-400 transition">
                                    <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-bold text-zinc-950 shadow-lg shadow-orange-500/25 transition hover:from-orange-400 hover:to-amber-400">
                        Create Account
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-zinc-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-orange-400 hover:text-orange-300">Sign in</a>
                </p>
            </div>
        </div>
    </div>
@endsection
