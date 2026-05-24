<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Sign in' }} | Avatech Smart-PMIS</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            background-color: #FAF5FF;
            background-image:
                radial-gradient(circle at 15% 50%, rgba(168, 85, 247, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(192, 132, 252, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased text-[#1E1B4B]">

    <main class="w-full max-w-md bg-white/80 backdrop-blur-xl rounded-2xl shadow-[0_25px_50px_-12px_rgba(124,58,237,0.15)] overflow-hidden relative">

        <div class="h-1 w-full bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC]"></div>

        <div class="py-12 px-8 sm:px-10">

            <div class="text-center mb-10">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#7C3AED] via-[#A855F7] to-[#C084FC] flex items-center justify-center shadow-[0_8px_20px_rgba(124,58,237,0.25)]">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-[18px] font-bold text-[#1E1B4B] tracking-tight">Avatech Nusantara</h1>
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.22em] text-violet-500/80 mt-1.5">Smart-PMIS</p>
            </div>

            @if (session('status'))
                <div class="mb-5 px-3.5 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-[12.5px] text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                <div>
                    <label for="email" class="block text-[13px] font-semibold text-[#1E1B4B] mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                            placeholder="you@avatech.test"
                            class="block w-full pl-11 pr-3 py-3 bg-white border border-violet-100 rounded-lg text-[14px] text-[#1E1B4B] placeholder:text-slate-400 focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200 transition"
                        />
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-[12px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[13px] font-semibold text-[#1E1B4B] mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            placeholder="&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;"
                            class="block w-full pl-11 pr-12 py-3 bg-white border border-violet-100 rounded-lg text-[14px] text-[#1E1B4B] placeholder:text-slate-400 focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200 transition"
                            data-password-input
                        />
                        <button
                            type="button"
                            data-password-toggle
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                            tabindex="0"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-violet-600 focus:outline-none focus:text-violet-600 cursor-pointer"
                        >
                            {{-- Eye (visible state) --}}
                            <svg data-icon-show class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            {{-- Eye-slash (hidden state, swapped via JS) --}}
                            <svg data-icon-hide class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-[12px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            value="1"
                            @checked(old('remember'))
                            class="w-4 h-4 rounded border-violet-200 text-violet-600 focus:ring-2 focus:ring-violet-200 cursor-pointer"
                        />
                        <span class="ml-2 text-[12.5px] text-slate-500">Keep me signed in</span>
                    </label>
                    <a href="#" class="text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">Forgot password?</a>
                </div>

                <div class="pt-3">
                    <button
                        type="submit"
                        class="group w-full flex items-center justify-center gap-2 py-3 px-4 rounded-lg text-white text-[14px] font-semibold tracking-wide bg-gradient-to-r from-[#7C3AED] to-[#A855F7] hover:from-[#6D28D9] hover:to-[#9333EA] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 active:translate-y-0 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-400 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                    >
                        <span>Sign In</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>

            </form>

            <div class="mt-8 text-center">
                <p class="text-[11px] tracking-[0.05em] text-slate-400 uppercase">Secure Enterprise Access Portal</p>
            </div>

        </div>
    </main>

    <script>
        (function () {
            const toggle = document.querySelector('[data-password-toggle]');
            const input = document.querySelector('[data-password-input]');
            const showIcon = toggle?.querySelector('[data-icon-show]');
            const hideIcon = toggle?.querySelector('[data-icon-hide]');
            if (! toggle || ! input || ! showIcon || ! hideIcon) return;

            toggle.addEventListener('click', () => {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                showIcon.classList.toggle('hidden', isHidden);
                hideIcon.classList.toggle('hidden', ! isHidden);
                toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggle.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
            });
        })();
    </script>

</body>
</html>
