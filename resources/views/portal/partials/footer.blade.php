<footer class="relative z-10 border-t border-[#E8E3DB] bg-[#F7F5F2] text-[#2A2520] dark:border-white/10 dark:bg-[#0D2232] dark:text-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-3">
            <div>
                <p class="font-display text-lg font-bold text-orange-600 dark:text-orange-400">{{ $settings->name ?? "Smoke n' Grill" }}</p>
                <p class="mt-2 text-sm leading-6 text-[#6B635A] dark:text-white/60">{{ $settings->description }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#8A8176] dark:text-white/40">Contact</p>
                <ul class="mt-3 space-y-2 text-sm text-[#6B635A] dark:text-white/60">
                    @if ($settings->address ?? false)
                        <li><i class="fa-solid fa-location-dot mr-2 text-orange-500" aria-hidden="true"></i>{{ $settings->address }}</li>
                    @endif
                    @if ($settings->phone ?? false)
                        <li><i class="fa-solid fa-phone mr-2 text-orange-500" aria-hidden="true"></i>{{ $settings->phone }}</li>
                    @endif
                    @if ($settings->opening_hours ?? false)
                        <li><i class="fa-solid fa-clock mr-2 text-orange-500" aria-hidden="true"></i>{{ $settings->opening_hours }}</li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#8A8176] dark:text-white/40">Quick Links</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('menu.index') }}" class="text-[#6B635A] transition hover:text-orange-600 dark:text-white/60 dark:hover:text-orange-400">View Menu</a></li>
                    <li><a href="{{ route('cart.index') }}" class="text-[#6B635A] transition hover:text-orange-600 dark:text-white/60 dark:hover:text-orange-400">Order Now</a></li>
                    @guest
                        <li><a href="{{ route('register') }}" class="text-[#6B635A] transition hover:text-orange-600 dark:text-white/60 dark:hover:text-orange-400">Create Account</a></li>
                    @endguest
                </ul>
            </div>
        </div>
        <p class="mt-10 border-t border-[#E8E3DB] pt-6 text-center text-xs text-[#8A8176] dark:border-white/10 dark:text-white/40">
            &copy; {{ date('Y') }} {{ $settings->name ?? "Smoke n' Grill" }}. All rights reserved.
        </p>
    </div>
</footer>
