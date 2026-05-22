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

        <form wire:submit="authenticate" class="space-y-5">

            <div>
                <label for="email" class="block text-[13px] font-semibold text-[#1E1B4B] mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <input
                        wire:model="data.email"
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        required
                        placeholder="you@avatech.test"
                        class="block w-full pl-11 pr-3 py-3 bg-white border border-violet-100 rounded-lg text-[14px] text-[#1E1B4B] placeholder:text-slate-400 focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200 transition"
                    />
                </div>
                @error('data.email')
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
                        wire:model="data.password"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        placeholder="&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;&bullet;"
                        class="block w-full pl-11 pr-3 py-3 bg-white border border-violet-100 rounded-lg text-[14px] text-[#1E1B4B] placeholder:text-slate-400 focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200 transition"
                    />
                </div>
                @error('data.password')
                    <p class="mt-1.5 text-[12px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer select-none">
                    <input
                        wire:model="data.remember"
                        type="checkbox"
                        id="remember"
                        class="w-4 h-4 rounded border-violet-200 text-violet-600 focus:ring-2 focus:ring-violet-200 cursor-pointer"
                    />
                    <span class="ml-2 text-[12.5px] text-slate-500">Keep me signed in</span>
                </label>
                <a href="#" class="text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">Forgot password?</a>
            </div>

            <div class="pt-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="authenticate"
                    class="group w-full flex items-center justify-center gap-2 py-3 px-4 rounded-lg text-white text-[14px] font-semibold tracking-wide bg-gradient-to-r from-[#7C3AED] to-[#A855F7] hover:from-[#6D28D9] hover:to-[#9333EA] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 active:translate-y-0 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-400 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                >
                    <span wire:loading.remove wire:target="authenticate">Sign In</span>
                    <span wire:loading wire:target="authenticate">Signing in...</span>
                    <svg wire:loading.remove wire:target="authenticate" class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
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
