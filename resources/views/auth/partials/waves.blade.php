{{-- Animated indigo / blue waves (bottom, full width) --}}
<div
    aria-hidden="true"
    class="pointer-events-none absolute inset-x-0 bottom-0 z-0 max-h-[min(42rem,55vh)] min-h-[32vh] overflow-hidden sm:min-h-[36vh]"
>
    <div class="absolute inset-0 bg-linear-to-t from-indigo-100/90 via-indigo-50/50 to-zinc-50"></div>

    <div class="absolute inset-x-0 bottom-0 h-[85%] min-h-28 origin-bottom -scale-y-100">
        <div class="login-waves-track login-waves-track--slow flex h-full w-[200%]">
            @foreach (range(1, 2) as $_)
                <svg
                    class="h-full min-w-[50%] shrink-0 text-violet-400/50"
                    preserveAspectRatio="none"
                    viewBox="0 0 1200 180"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill="currentColor"
                        d="M0,95 C200,45 400,145 600,95 C800,45 1000,145 1200,95 L1200,180 L0,180 Z"
                    />
                </svg>
            @endforeach
        </div>
    </div>

    <div class="absolute inset-x-0 bottom-0 h-full min-h-32 origin-bottom -scale-y-100">
        <div class="login-waves-track flex h-full w-[200%]">
            @foreach (range(1, 2) as $_)
                <svg
                    class="h-full min-w-[50%] shrink-0 text-indigo-400/55"
                    preserveAspectRatio="none"
                    viewBox="0 0 1200 180"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill="currentColor"
                        d="M0,88 C220,38 420,138 620,88 C820,38 1020,138 1200,88 L1200,180 L0,180 Z"
                    />
                </svg>
            @endforeach
        </div>
    </div>
</div>
