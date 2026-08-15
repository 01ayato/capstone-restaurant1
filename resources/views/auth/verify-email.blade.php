@extends('layouts.guest-dark')

@section('title', 'Verify Your Email')

@section('content')
    <div class="relative flex min-h-dvh flex-col justify-center px-4 py-12 sm:px-6">
        <div class="relative z-10 mx-auto w-full max-w-md">
            <div class="rounded-3xl border border-white/10 bg-zinc-900/80 p-10 shadow-2xl shadow-orange-500/5 backdrop-blur-xl ring-1 ring-white/5">
                <div class="flex justify-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 text-2xl text-zinc-950 shadow-lg shadow-orange-500/30" aria-hidden="true">
                        <i class="fa-solid fa-envelope-circle-check"></i>
                    </div>
                </div>

                <h1 class="mt-6 text-center font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Verify Your Email
                </h1>
                <p class="mt-2 text-center text-sm text-zinc-400">
                    We sent a 6-digit code to <strong class="text-white">{{ auth()->user()->email }}</strong>
                </p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('auth.verify-email.perform') }}" method="post" class="mt-8 space-y-6">
                    @csrf

                    <div>
                        <label for="code" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-500">Verification Code</label>
                        <input
                            id="code"
                            type="text"
                            name="code"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            placeholder="000000"
                            required
                            autofocus
                            class="sg-input text-center text-2xl font-bold tracking-widest"
                            value="{{ old('code') }}"
                        >
                    </div>

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-bold text-zinc-950 shadow-lg shadow-orange-500/25 transition hover:from-orange-400 hover:to-amber-400">
                        Verify Email
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-zinc-500">
                    Didn't receive a code?
                    <form action="{{ route('auth.resend-code') }}" method="post" class="inline">
                        @csrf
                        <button type="submit" class="font-semibold text-orange-400 hover:text-orange-300">
                            Resend
                        </button>
                    </form>
                </p>

                <p class="mt-4 text-center">
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-zinc-600 hover:text-zinc-400">
                            Sign out
                        </button>
                    </form>
                </p>
            </div>
        </div>
    </div>
@endsection
