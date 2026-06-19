<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LogiMind') — Copilote logistique IA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        ink: '#0a0f1f',
                        panel: '#111a32',
                        brand: { DEFAULT: '#10e5a4', deep: '#06b6d4' },
                    },
                    keyframes: {
                        floaty: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } },
                    },
                    animation: { floaty: 'floaty 6s ease-in-out infinite' },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { background: radial-gradient(1200px 600px at 80% -10%, #0e2a3f 0%, #0a0f1f 45%) , #0a0f1f; }
        .glass { background: rgba(17,26,50,.6); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.07); }
        .glow { box-shadow: 0 0 0 1px rgba(16,229,164,.15), 0 18px 60px -20px rgba(16,229,164,.35); }
        .grad-text { background: linear-gradient(100deg,#10e5a4,#06b6d4); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .leaflet-container { background:#0a0f1f; border-radius: 1rem; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #1e2a4a; border-radius: 8px; }
    </style>
    @stack('head')
</head>
<body class="font-sans text-slate-200 antialiased min-h-screen">

    <header class="sticky top-0 z-30 glass">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-brand-deep grid place-items-center text-ink font-extrabold text-lg">L</div>
                <div>
                    <div class="font-extrabold tracking-tight leading-none text-white">LogiMind <span class="text-brand">Maroc</span></div>
                    <div class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Copilote logistique IA</div>
                </div>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                @php $r = request()->routeIs(...); @endphp
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Tableau de bord</a>
                <a href="{{ route('port') }}" class="px-4 py-2 rounded-lg transition {{ request()->routeIs('port') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Optimisation portuaire</a>
                <a href="{{ route('routing') }}" class="px-4 py-2 rounded-lg transition {{ request()->routeIs('routing') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Fluidité urbaine</a>
            </nav>
            <div class="hidden md:flex items-center gap-2 text-xs text-slate-400">
                <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span> Données temps réel
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10">
        @yield('content')
    </main>

    <footer class="max-w-7xl mx-auto px-6 py-10 text-xs text-slate-500 border-t border-white/5 mt-10">
        LogiMind Maroc — MVP démo · Mer → Terre · {{ now()->year }}
    </footer>

    @stack('scripts')
</body>
</html>
