<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SmartPort') — Copilote logistique IA</title>

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
        .grad-text { color: #10e5a4; }
        .leaflet-container { background:#0a0f1f; border-radius: 1rem; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #1e2a4a; border-radius: 8px; }
        .ai-spin { width:15px; height:15px; border:2px solid rgba(16,229,164,.25); border-top-color:#10e5a4; border-radius:50%; display:inline-block; animation:aispin .7s linear infinite; vertical-align:-2px; }
        @keyframes aispin { to { transform: rotate(360deg) } }
        [data-ai][data-ai-loading="1"] { animation: aipulse 1.4s ease-in-out infinite; }
        @keyframes aipulse { 0%,100% { opacity:.55 } 50% { opacity:.9 } }

        /* Cinematic video background (cover-fit relative to its container) */
        .video-bg { position:absolute; inset:0; overflow:hidden; container-type:size; z-index:0; }
        .video-bg iframe {
            position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
            width:max(100cqw, 177.78cqh); height:max(100cqh, 56.26cqw);
            border:0; pointer-events:none;
        }
        .video-bg::after { /* gentle vignette so edges fade into the panel */
            content:""; position:absolute; inset:0;
            box-shadow: inset 0 0 120px 30px rgba(10,15,31,.9);
        }

        /* Global ambient video behind the whole app (every page) */
        .site-video { position:fixed; inset:0; overflow:hidden; container-type:size; z-index:-2; }
        .site-video iframe {
            position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
            width:max(100cqw, 177.78cqh); height:max(100cqh, 56.26cqw);
            border:0; pointer-events:none;
        }
        .site-video-veil { /* keep maps, tables and text readable over the footage */
            position:fixed; inset:0; z-index:-1; pointer-events:none;
            background:
                radial-gradient(1200px 600px at 80% -10%, rgba(14,42,63,.55) 0%, transparent 55%),
                linear-gradient(180deg, rgba(10,15,31,.86) 0%, rgba(10,15,31,.92) 100%);
        }
        @media (prefers-reduced-motion: reduce) { .video-bg, .site-video { display:none; } }

        /* Scroll reveal + interactive motion */
        .reveal { opacity:0; transform:translateY(18px); transition:opacity .55s ease, transform .55s cubic-bezier(.2,.7,.2,1); will-change:opacity,transform; }
        .reveal.in { opacity:1; transform:none; }
        main .glass { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        main .glass:hover:not(:has(.leaflet-container)) { transform: translateY(-3px); border-color: rgba(16,229,164,.28); }
        a.navlink { position:relative; }
        a.navlink::after { content:""; position:absolute; left:12px; right:12px; bottom:4px; height:2px; background:#10e5a4; transform:scaleX(0); transform-origin:left; transition:transform .25s ease; border-radius:2px; }
        a.navlink:hover::after { transform:scaleX(1); }
        header .logo-s { transition: transform .4s cubic-bezier(.2,.7,.2,1); }
        header a:hover .logo-s { transform: rotate(-8deg) scale(1.06); }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity:1 !important; transform:none !important; }
            main .glass:hover { transform:none; }
        }
    </style>
    @stack('head')
</head>
<body class="font-sans text-slate-200 antialiased min-h-screen">

    {{-- Ambient cargo-ship footage behind the whole app (muted, looping, decorative) --}}
    <div class="site-video" aria-hidden="true">
        <iframe
            src="https://www.youtube.com/embed/wQMx7wc4jh8?autoplay=1&mute=1&loop=1&playlist=wQMx7wc4jh8&controls=0&showinfo=0&modestbranding=1&rel=0&iv_load_policy=3&disablekb=1&playsinline=1&fs=0&start=3"
            title="Vue drone porte-conteneurs" allow="autoplay; encrypted-media" referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    <div class="site-video-veil" aria-hidden="true"></div>

    <header class="sticky top-0 z-30 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="h-16 flex items-center justify-between gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="logo-s w-9 h-9 rounded-xl bg-brand grid place-items-center text-ink font-extrabold text-lg">S</div>
                    <div>
                        <div class="font-extrabold tracking-tight leading-none text-white">SmartPort <span class="text-brand">Maroc</span></div>
                        <div class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Copilote logistique IA</div>
                    </div>
                </a>
                {{-- nav desktop --}}
                <nav class="hidden lg:flex items-center gap-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Tableau de bord</a>
                    <a href="{{ route('tracking') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('tracking') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Suivi live</a>
                    <a href="{{ route('parcours') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('parcours') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Parcours</a>
                    <a href="{{ route('planification') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('planification') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Planification ETA</a>
                    <a href="{{ route('port') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('port') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Portuaire</a>
                    <a href="{{ route('routing') }}" class="navlink px-3 py-2 rounded-lg transition {{ request()->routeIs('routing') ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">Fluidité urbaine</a>
                </nav>
                <div class="hidden lg:flex items-center gap-2 text-xs text-slate-400 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span> Temps réel
                </div>
            </div>
            {{-- nav mobile (scrollable) --}}
            <nav class="lg:hidden flex items-center gap-1 text-sm overflow-x-auto pb-2 -mt-1">
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Tableau de bord</a>
                <a href="{{ route('tracking') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('tracking') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Suivi live</a>
                <a href="{{ route('parcours') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('parcours') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Parcours</a>
                <a href="{{ route('planification') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('planification') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Planification ETA</a>
                <a href="{{ route('port') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('port') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Portuaire</a>
                <a href="{{ route('routing') }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ request()->routeIs('routing') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Fluidité urbaine</a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
        @yield('content')
    </main>

    <footer class="max-w-7xl mx-auto px-6 py-10 text-xs text-slate-500 border-t border-white/5 mt-10">
        SmartPort Maroc — MVP démo · Mer → Terre · {{ now()->year }}
    </footer>

    {{-- Trafic maritime ambiant (navires AIS + ports régionaux) — couche décorative, ne nourrit aucun calcul --}}
    <script>
    window.SmartPortTraffic = (function () {
        // Couloirs maritimes (boîtes en pleine eau) : [latMin, latMax, lngMin, lngMax, densité]
        const LANES = [
            [35.84, 36.04, -5.92, -5.24, 14], // Détroit de Gibraltar (dense)
            [35.55, 36.20, -4.60, -3.05, 10], // Mer d'Alboran
            [35.00, 35.88, -6.65, -6.02,  8], // Atlantique large de Tanger/Larache
            [33.35, 34.25, -8.55, -7.62, 10], // Atlantique large de Casablanca/Mohammedia
            [32.10, 33.25, -9.70, -9.02,  6], // Large de Safi / Jorf Lasfar
            [30.10, 30.78, -10.35, -9.66, 6], // Large d'Agadir
            [35.20, 35.95, -3.55, -2.35,  7], // Méditerranée large de Nador/Al Hoceima
            [27.30, 29.40, -13.70, -11.40, 7], // Approche des Canaries / Sud
        ];
        const PREFIX = ['MAERSK','MSC','CMA CGM','HAPAG','EVERGREEN','COSCO','ONE','OOCL','HMM','ZIM','APL','YANG MING','NYK','ATLAS','IBN BATTOUTA','SAHARA','RIF','SOUSS','TANGIER','CASA','AL BORAK','MARRAKECH'];
        const SUFFIX = ['EXPRESS','TRADER','STAR','PIONEER','VOYAGER','SPIRIT','BRIDGE','HARMONY','WAVE','GLORY','HORIZON','MERIDIAN','BREEZE','SUMMIT'];
        const TYPES = [
            { t:'Porte-conteneurs', c:'#10e5a4' },
            { t:'Vraquier',         c:'#f59e0b' },
            { t:'Pétrolier',        c:'#ef4444' },
            { t:'Cargo',            c:'#22d3ee' },
            { t:'Roulier',          c:'#a78bfa' },
            { t:'Pêche',            c:'#94a3b8' },
        ];
        const PORTS = [
            { n:'Tanger Med',   lat:35.88, lng:-5.50, ma:true },
            { n:'Casablanca',   lat:33.60, lng:-7.62, ma:true },
            { n:'Mohammedia',   lat:33.72, lng:-7.39, ma:true },
            { n:'Kénitra',      lat:34.26, lng:-6.60, ma:true },
            { n:'Jorf Lasfar',  lat:33.13, lng:-8.62, ma:true },
            { n:'Safi',         lat:32.30, lng:-9.25, ma:true },
            { n:'Agadir',       lat:30.42, lng:-9.62, ma:true },
            { n:'Nador',        lat:35.27, lng:-2.93, ma:true },
            { n:'Al Hoceïma',   lat:35.25, lng:-3.93, ma:true },
            { n:'Tan-Tan',      lat:28.50, lng:-11.33, ma:true },
            { n:'Laâyoune',     lat:27.10, lng:-13.42, ma:true },
            { n:'Dakhla',       lat:23.70, lng:-15.94, ma:true },
            { n:'Algésiras',    lat:36.13, lng:-5.44, ma:false },
            { n:'Gibraltar',    lat:36.14, lng:-5.35, ma:false },
            { n:'Cádiz',        lat:36.53, lng:-6.28, ma:false },
            { n:'Tarifa',       lat:36.01, lng:-5.61, ma:false },
            { n:'Las Palmas',   lat:28.14, lng:-15.41, ma:false },
            { n:'S/C Tenerife', lat:28.47, lng:-16.25, ma:false },
        ];

        // PRNG déterministe -> les positions ne sautent pas à chaque refresh
        let seed = 73199;
        const rnd = () => (seed = (seed * 1103515245 + 12345) & 0x7fffffff) / 0x7fffffff;
        const pick = a => a[Math.floor(rnd() * a.length)];

        function buildShips() {
            const out = [];
            for (const [laMin, laMax, lnMin, lnMax, n] of LANES) {
                for (let i = 0; i < n; i++) {
                    const ty = pick(TYPES);
                    const anchored = rnd() < 0.12;
                    out.push({
                        name: pick(PREFIX) + ' ' + pick(SUFFIX),
                        type: ty.t, color: ty.c,
                        lat: laMin + rnd() * (laMax - laMin),
                        lng: lnMin + rnd() * (lnMax - lnMin),
                        head: Math.floor(rnd() * 360),
                        sog: anchored ? 0 : +(6 + rnd() * 13).toFixed(1),
                        mmsi: 200000000 + Math.floor(rnd() * 99999999),
                    });
                }
            }
            return out;
        }
        const SHIPS = buildShips();

        // Marqueur style traceur AIS : triangle orienté selon le cap
        function shipIcon(color, head, anchored) {
            const html = anchored
                ? `<div style="width:9px;height:9px;border:1.5px solid ${color};border-radius:50%;background:transparent"></div>`
                : `<svg width="13" height="13" viewBox="0 0 12 12" style="transform:rotate(${head}deg)">
                       <path d="M6 0 L10.5 11 L6 8.4 L1.5 11 Z" fill="${color}" fill-opacity="0.92"/>
                   </svg>`;
            return L.divIcon({ html, className: '', iconSize: [13, 13], iconAnchor: [6.5, 6.5] });
        }

        function add(map, opts = {}) {
            const g = L.layerGroup().addTo(map);

            PORTS.forEach(p => {
                const col = p.ma ? '#10e5a4' : '#64748b';
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="display:flex;align-items:center;gap:3px;white-space:nowrap">
                             <span style="font-size:13px;line-height:1;color:${col};text-shadow:0 1px 3px #000">⚓</span>
                             <span style="font-size:9px;font-weight:700;color:${col};text-shadow:0 1px 3px #000">${p.n}</span>
                           </div>`,
                    iconSize: [10, 10], iconAnchor: [5, 5],
                });
                L.marker([p.lat, p.lng], { icon, interactive: true, opacity: opts.portOpacity ?? 0.85 })
                    .bindPopup(`<b>${p.n}</b><br>${p.ma ? 'Port marocain' : 'Port régional'}`)
                    .addTo(g);
            });

            SHIPS.forEach(s => {
                L.marker([s.lat, s.lng], {
                    icon: shipIcon(s.color, s.head, s.sog === 0),
                    opacity: opts.shipOpacity ?? 0.85,
                    interactive: true,
                }).bindPopup(
                    `<b>${s.name}</b><br>${s.type}<br>` +
                    `${s.sog === 0 ? "À l'ancre" : s.sog + ' nœuds'} · MMSI ${s.mmsi}`
                ).addTo(g);
            });

            return g;
        }

        return { add, SHIPS, PORTS };
    })();
    </script>

    @stack('scripts')

    {{-- Révélation au défilement (fade-up séquencé) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const blocks = [...document.querySelectorAll('main > *')];
            blocks.forEach(b => b.classList.add('reveal'));
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (!e.isIntersecting) return;
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
            blocks.forEach((b, i) => {
                b.style.transitionDelay = (Math.min(i, 6) * 70) + 'ms';
                io.observe(b);
            });
        });
    </script>

    {{-- Chargement asynchrone des textes IA (spinner -> texte) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const els = [...document.querySelectorAll('[data-ai][data-ai-loading="1"]')];
            if (!els.length) return;
            const url = new URL(window.location.href);
            url.searchParams.set('ai', '1');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    els.forEach(el => {
                        const k = el.dataset.ai;
                        if (data[k] != null) {
                            el.textContent = data[k];
                            el.setAttribute('data-ai-loading', '0');
                        }
                    });
                })
                .catch(() => els.forEach(el => { el.textContent = 'Analyse momentanément indisponible.'; el.setAttribute('data-ai-loading','0'); }));
        });
    </script>
</body>
</html>
