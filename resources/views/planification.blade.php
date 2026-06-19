@extends('layouts.app')
@section('title', 'Planification ETA')

@php $fmt = fn($d) => $d->locale('fr')->isoFormat('ddd D MMM'); @endphp

@section('content')
    <div class="mb-8">
        <div class="text-xs font-bold uppercase tracking-widest text-brand">Prédictif · Départ → Arrivée</div>
        <h1 class="mt-1 text-3xl font-extrabold text-white">Planification & ETA prédictive</h1>
        <p class="text-slate-400 mt-1">Liaison maritime, transbordement, type de chargement — SmartPort prédit la fenêtre d'arrivée</p>
    </div>

    {{-- FORMULAIRE --}}
    <form method="GET" class="glass rounded-2xl p-6 mb-8 grid sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
        <div class="lg:col-span-2">
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Port de départ</label>
            <select name="origine" class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($origins as $o)
                    <option value="{{ $o }}" @selected($o === $originKey) class="bg-ink">{{ $o }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Arrivée</label>
            <select name="port" class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($ports as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $dest->id) class="bg-ink">{{ $p->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Départ</label>
            <input type="date" name="depart" value="{{ $depart->toDateString() }}"
                   class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Type</label>
            <select name="type" class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                <option value="FCL" @selected($type==='FCL') class="bg-ink">FCL (complet)</option>
                <option value="LCL" @selected($type==='LCL') class="bg-ink">LCL (groupage)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Liaison</label>
            <select name="routing" class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                <option value="Transbordement" @selected($routing==='Transbordement') class="bg-ink">Transbordement</option>
                <option value="Direct" @selected($routing==='Direct') class="bg-ink">Direct</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Armateur</label>
            <select name="carrier" class="w-full glass rounded-xl px-3 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($carriers as $c)
                    <option value="{{ $c }}" @selected($c === $carrier) class="bg-ink">{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="lg:col-span-6 sm:col-span-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand to-brand-deep text-ink font-bold hover:opacity-90 transition">
            Prédire la fenêtre d'arrivée →
        </button>
    </form>

    <div class="grid lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 space-y-6">
            {{-- 3 SCENARIOS ETA --}}
            <section class="relative overflow-hidden rounded-3xl glass glow p-8">
                <div class="absolute -top-20 -right-12 w-72 h-72 bg-brand/20 blur-3xl rounded-full animate-floaty"></div>
                <div class="relative">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="text-xs font-bold uppercase tracking-widest text-brand">Arrivée prévue · {{ $dest->nom }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $pred['type'] }} ·
                            @if ($pred['hub']) transbordement {{ $pred['hub'] }} @else liaison directe @endif
                            · {{ $pred['carrier'] }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-white/5 p-4 text-center">
                            <div class="text-[10px] text-emerald-300 uppercase tracking-wide font-bold">Optimiste</div>
                            <div class="mt-1 text-lg font-extrabold text-white capitalize">{{ $fmt($pred['eta_optimiste']) }}</div>
                        </div>
                        <div class="rounded-2xl bg-brand/15 ring-1 ring-brand/40 p-4 text-center">
                            <div class="text-[10px] text-brand uppercase tracking-wide font-bold">Réaliste</div>
                            <div class="mt-1 text-xl font-extrabold text-white capitalize">{{ $fmt($pred['eta_realiste']) }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-4 text-center">
                            <div class="text-[10px] text-rose-300 uppercase tracking-wide font-bold">Pessimiste</div>
                            <div class="mt-1 text-lg font-extrabold text-white capitalize">{{ $fmt($pred['eta_pessimiste']) }}</div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-extrabold text-white">{{ $pred['transit_min'] }}–{{ $pred['transit_max'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase">jours transit</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-extrabold text-amber-300">+{{ $pred['retard_total'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase">jours retard anticipé</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-extrabold text-brand">{{ $pred['confiance'] }}%</div>
                            <div class="text-[10px] text-slate-400 uppercase">confiance</div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- DECOMPOSITION DES RETARDS --}}
            <section class="glass rounded-2xl p-6">
                <h3 class="font-bold text-white flex items-center gap-2 mb-4">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/15 grid place-items-center">⏱️</span>
                    Facteurs de retard anticipés
                </h3>
                <div class="space-y-3">
                    @foreach ($pred['retards'] as $r)
                        <div class="flex items-center justify-between gap-4 py-2 border-b border-white/5 last:border-0">
                            <div>
                                <div class="text-sm text-white font-medium">{{ $r['label'] }}</div>
                                <div class="text-xs text-slate-400">{{ $r['note'] }}</div>
                            </div>
                            <div class="text-lg font-bold {{ $r['jours'] > 0 ? 'text-amber-300' : 'text-emerald-300' }}">+{{ $r['jours'] }}j</div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ANALYSE IA --}}
            <section class="glass rounded-2xl p-6">
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">✦</span>
                    Analyse prédictive — SmartPort IA
                </div>
                <p class="mt-4 text-white leading-relaxed">{{ $analyse }}</p>
            </section>
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- MAP --}}
            <div class="glass rounded-2xl p-2">
                <div id="map" class="w-full h-[300px] rounded-xl"></div>
            </div>

            {{-- SUIVI B/L --}}
            <div class="glass rounded-2xl p-6">
                <h3 class="font-bold text-white flex items-center gap-2 mb-4">
                    <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">📄</span>
                    Suivi du conteneur
                </h3>
                <div class="text-xs text-slate-400 uppercase tracking-wide">N° de connaissement (B/L)</div>
                <div class="mt-1 flex items-center gap-2">
                    <code class="text-lg font-bold text-brand tracking-wider">{{ $pred['bl_number'] }}</code>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-400">Armateur</span><span class="text-white">{{ $pred['carrier'] }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Liaison</span><span class="text-white">{{ $pred['routing'] }}@if($pred['hub']) · {{ $pred['hub'] }}@endif</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Chargement</span><span class="text-white">{{ $pred['type'] }}</span></div>
                </div>
                <a href="https://www.searates.com/container/tracking/?number={{ $pred['bl_number'] }}" target="_blank" rel="noopener"
                   class="mt-5 block text-center px-4 py-2.5 rounded-xl border border-white/15 text-white font-semibold hover:bg-white/5 transition">
                    Suivre sur Searates ↗
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { zoomControl: true, attributionControl: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    const origin = [{{ $pred['origin_coords']['lat'] }}, {{ $pred['origin_coords']['lng'] }}];
    const dest = [{{ $dest->lat }}, {{ $dest->lng }}];

    const oIcon = L.divIcon({ html: '<div style="font-size:20px;filter:drop-shadow(0 0 4px rgba(6,182,212,.7))">🏭</div>', className:'', iconSize:[22,22], iconAnchor:[11,11] });
    const dIcon = L.divIcon({ html: '<div style="font-size:20px;filter:drop-shadow(0 0 4px rgba(16,229,164,.7))">⚓</div>', className:'', iconSize:[22,22], iconAnchor:[11,11] });

    L.marker(origin, { icon: oIcon }).addTo(map).bindPopup('<b>{{ $originKey }}</b><br>Départ');
    L.marker(dest, { icon: dIcon }).addTo(map).bindPopup('<b>{{ $dest->nom }}</b><br>Arrivée');

    const seaPath = @json($seaPath);
    L.polyline(seaPath, { color: '#06b6d4', weight: 3, opacity: 0.85 }).addTo(map);
    map.fitBounds(seaPath, { padding: [40, 40] });
</script>
@endpush
