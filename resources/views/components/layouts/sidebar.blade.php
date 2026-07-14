@php
    $navigation = config('navigation');
    $role       = auth()->user()?->roles?->first()?->name;
    $groupKey   = $role ? ($navigation['role_to_group'][$role] ?? null) : null;
    $groups     = $groupKey ? ($navigation['groups'][$groupKey] ?? []) : [];
@endphp

{{-- Pre-render guard: read collapsed state synchronously so the aside paints in its final width (no FOUC) --}}
<script>
    (function () {
        try {
            if (localStorage.getItem('avt-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>

<style>
    aside[data-sidebar] {
        transition: width 200ms ease, padding-left 200ms ease, padding-right 200ms ease;
    }
    aside[data-sidebar] nav {
        scrollbar-width: thin;
        scrollbar-color: rgba(124,58,237,0.2) transparent;
    }
    aside[data-sidebar] nav::-webkit-scrollbar       { width: 6px; }
    aside[data-sidebar] nav::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.2); border-radius: 9999px; }
    aside[data-sidebar] nav::-webkit-scrollbar-track { background: transparent; }
    [data-sidebar-collapse-icon-collapse] { display: none; }

    html.sidebar-collapsed aside[data-sidebar] {
        width: 5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    html.sidebar-collapsed [data-sidebar-brand],
    html.sidebar-collapsed [data-sidebar-section-label],
    html.sidebar-collapsed [data-sidebar-text],
    html.sidebar-collapsed [data-sidebar-pd-header],
    html.sidebar-collapsed [data-sidebar-divider] {
        display: none !important;
    }
    html.sidebar-collapsed [data-sidebar-brand-wrap] {
        justify-content: center;
        gap: 0;
        padding-left: 0;
        padding-right: 0;
    }
    html.sidebar-collapsed [data-sidebar-item] {
        justify-content: center;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
        gap: 0;
    }
    html.sidebar-collapsed [data-sidebar-active] {
        margin-left: 0 !important;
        border-radius: 1rem !important;
    }
    html.sidebar-collapsed [data-sidebar-pd-item] {
        justify-content: center;
        padding-left: 0 !important;
        padding-right: 0 !important;
        gap: 0;
    }
    html.sidebar-collapsed [data-sidebar-collapse-icon-expand]  { display: none; }
    html.sidebar-collapsed [data-sidebar-collapse-icon-collapse] { display: inline-block; }
</style>

<aside data-sidebar class="w-72 flex-shrink-0 flex flex-col h-screen sticky top-0 z-40 bg-card px-5 shadow-[6px_0_24px_-10px_rgb(124_58_237/0.08)]">
    <div data-sidebar-brand-wrap class="py-10 flex items-center gap-4 px-2 mb-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-violet-400 text-white flex items-center justify-center flex-shrink-0 shadow-lg shadow-violet-500/25">
            <x-heroicon-o-sparkles class="w-7 h-7" />
        </div>
        <div data-sidebar-brand class="min-w-0">
            <p class="text-base font-bold text-primary truncate leading-tight tracking-tight">Avatech Nusantara</p>
            <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-muted-foreground mt-1.5">Smart-PMIS</p>
        </div>
    </div>

    @if (! empty($groups))
        <nav class="flex-1 min-h-0 overflow-y-auto flex flex-col gap-14 py-6">
            @foreach ($groups as $group)
                <div class="flex flex-col">
                    <p data-sidebar-section-label class="px-4 text-[11px] font-bold tracking-[0.2em] text-muted-foreground uppercase mb-8">
                        {{ $group['label'] }}
                    </p>
                    <ul class="flex flex-col gap-3">
                        @foreach ($group['items'] as $item)
                            @php
                                $hasRoute = \Illuminate\Support\Facades\Route::has($item['route']);
                                $url      = $hasRoute ? route($item['route']) : '#';
                                $active   = $hasRoute && request()->routeIs($item['route']);
                            @endphp
                            <li>
                                <a
                                    href="{{ $url }}"
                                    title="{{ $item['label'] }}"
                                    data-sidebar-item
                                    @if ($active) data-sidebar-active @endif
                                    @class([
                                        'group flex items-center gap-4 text-sm transition-all duration-200',
                                        'px-4 py-5 rounded-2xl bg-gradient-to-r from-[#4f378a] to-[#c084fc] text-white font-semibold shadow-[0_12px_24px_-8px_rgb(124_58_237/0.35)]' => $active,
                                        'px-4 py-5 rounded-2xl text-foreground/80 font-medium hover:bg-violet-50/60 hover:text-primary' => ! $active,
                                    ])
                                >
                                    <x-dynamic-component
                                        :component="'heroicon-o-' . $item['icon']"
                                        :class="$active
                                            ? 'w-[22px] h-[22px] flex-shrink-0 text-white [stroke-width:1.25]'
                                            : 'w-[22px] h-[22px] flex-shrink-0 text-muted-foreground/70 group-hover:text-primary [stroke-width:1.25] transition-colors'"
                                    />
                                    <span data-sidebar-text>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($group['label'] === 'MAIN' && request()->routeIs('projects.show'))
                        @php
                            $currentProject = request()->route('project');
                            $projectName    = $currentProject?->name ?? 'Proyek aktif';
                            $pdNavItems = [
                                ['id' => 'overview',   'label' => 'Overview'],
                                ['id' => 'workspace',  'label' => 'Kanban Workspace'],
                                ['id' => 'aiplanning', 'label' => 'AI Planning'],
                                ['id' => 'dependencies', 'label' => 'Dependencies'],
                                ['id' => 'timeline', 'label' => 'Timeline'],
                                ['id' => 'qc',         'label' => 'Quality Control'],
                            ];
                        @endphp
                        <div class="mt-10 flex flex-col">
                            <div data-sidebar-pd-header class="px-4 mb-5">
                                <p class="text-[11px] font-bold tracking-[0.22em] text-primary uppercase">Workspace</p>
                                <p class="text-[10px] italic mt-1 text-primary/70 truncate">{{ $projectName }} aktif</p>
                            </div>
                            <ul class="flex flex-col gap-1.5">
                                @foreach ($pdNavItems as $idx => $pd)
                                    <li>
                                        <a
                                            href="#{{ $pd['id'] }}"
                                            data-pd-nav="{{ $pd['id'] }}"
                                            data-sidebar-pd-item
                                            title="{{ $pd['label'] }}"
                                            @class([
                                                'pd-side-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-[13px] transition-all duration-200',
                                                'is-active bg-[#F5F3FF] text-primary font-bold' => $idx === 0,
                                                'text-muted-foreground hover:text-primary hover:bg-violet-50/60 font-medium' => $idx !== 0,
                                            ])
                                        >
                                            <span class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                                            <span data-sidebar-text>{{ $pd['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
    @endif

    @php
        $settingsHas    = \Illuminate\Support\Facades\Route::has('settings.index');
        $settingsUrl    = $settingsHas ? route('settings.index') : '#';
        $settingsActive = $settingsHas && request()->routeIs('settings.index');
        /*
         * Settings today is workspace/system settings (CEO/PM scope).
         * Operational roles get no Settings entry until a personal-account
         * settings page exists — they would only see admin controls otherwise.
         */
        $showSettings = in_array($role, ['ceo_pm', 'admin', 'super_admin', 'developer'], true);
    @endphp
    <div class="flex-shrink-0 pb-8 flex flex-col gap-2">
        <div data-sidebar-divider class="h-px bg-border/50 mx-4 mb-2"></div>
        @if ($showSettings)
            <a
                href="{{ $settingsUrl }}"
                title="Settings"
                data-sidebar-item
                @if ($settingsActive) data-sidebar-active @endif
                @class([
                    'group flex items-center gap-4 text-sm transition-all duration-200',
                    'px-4 py-5 rounded-2xl bg-gradient-to-r from-[#4f378a] to-[#c084fc] text-white font-semibold shadow-[0_12px_24px_-8px_rgb(124_58_237/0.35)]' => $settingsActive,
                    'px-4 py-5 rounded-2xl text-foreground/80 font-medium hover:bg-violet-50/60 hover:text-primary' => ! $settingsActive,
                ])
            >
                <x-heroicon-o-cog-6-tooth
                    :class="$settingsActive
                        ? 'w-[22px] h-[22px] flex-shrink-0 text-white [stroke-width:1.25]'
                        : 'w-[22px] h-[22px] flex-shrink-0 text-muted-foreground/70 group-hover:text-primary [stroke-width:1.25] transition-colors'"
                />
                <span data-sidebar-text>Settings</span>
            </a>
        @endif
        <button
            type="button"
            data-sidebar-collapse-toggle
            data-sidebar-item
            title="Collapse"
            aria-label="Collapse sidebar"
            class="group flex items-center gap-4 px-4 py-5 rounded-2xl text-sm font-medium text-foreground/80 hover:bg-violet-50/60 hover:text-primary transition-all duration-200 text-left w-full cursor-pointer"
        >
            <x-heroicon-o-chevron-double-left data-sidebar-collapse-icon-expand class="w-[22px] h-[22px] flex-shrink-0 text-muted-foreground/70 group-hover:text-primary [stroke-width:1.25] transition-colors" />
            <x-heroicon-o-chevron-double-right data-sidebar-collapse-icon-collapse class="w-[22px] h-[22px] flex-shrink-0 text-muted-foreground/70 group-hover:text-primary [stroke-width:1.25] transition-colors" />
            <span data-sidebar-text>Collapse</span>
        </button>
    </div>
</aside>

<script>
    (function () {
        const wire = () => {
            const toggle = document.querySelector('[data-sidebar-collapse-toggle]');
            if (! toggle) return;
            toggle.addEventListener('click', () => {
                const next = ! document.documentElement.classList.contains('sidebar-collapsed');
                document.documentElement.classList.toggle('sidebar-collapsed', next);
                try { localStorage.setItem('avt-sidebar-collapsed', next ? '1' : '0'); } catch (e) {}
            });
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', wire);
        } else {
            wire();
        }
    })();
</script>
