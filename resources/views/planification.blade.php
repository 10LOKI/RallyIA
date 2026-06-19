@extends('layouts.app')
@section('title', 'Planification ETA')

@section('content')
    <div class="mb-8">
        <div class="text-xs font-bold uppercase tracking-widest text-brand">Prédictif · Départ → Arrivée</div>
        <h1 class="mt-1 text-3xl font-extrabold text-white">Planification & ETA prédictive</h1>
        <p class="text-slate-400 mt-1">Choisissez votre créneau de départ — LogiMind prédit la date d'arrivée</p>
    </div>

    {{-- FORMULAIRE --}}
    <form method="GET" class="glass rounded-2xl p-6 mb-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Port de départ</label>
            <select name="origine" class="w-full glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($origins as $o)
                    <option value="{{ $o }}" @selected($o === $originKey) class="bg-ink">{{ $o }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Port d'arrivée (Maroc)</label>
            <select name="port" class="w-full glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($ports as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $dest->id) class="bg-ink">{{ $p->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 uppercase tracking-wide mb-1.5">Créneau de départ</label>
            <input type="date" name="depart" value="{{ $depart->toDateString() }}"
                   class="w-full glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand to-brand-deep text-ink font-bold hover:opacity-90 transition">
            Prédire l'arrivée →
        </button>
    </form>

    {{-- RESULTAT --}}
    <div class="grid lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 space-y-6">
            {{-- ETA card --}}
            <section class="relative overflow-hidden rounded-3xl glass glow p-8">
                <div class="absolute -top-20 -right-12 w-72 h-72 bg-brand/20 blur-3xl rounded-full animate-floaty"></div>
                <div class="relative">
                    <div class="text-xs font-bold uppercase tracking-widest text-brand">Arrivée prévue à {{ $dest->nom }}</div>
                    <div class="mt-3 text-4xl md:text-5xl font-extrabold text-white capitalize">
                        {{ $pred['eta_base']->locale('fr')->isoFormat('dddd D MMMM') }}
                    </div>
                    <div class="mt-2 text-slate-300">
                        Fenêtre réaliste :
                        <span class="text-white font-semibold capitalize">{{ $pred['eta_min']->locale('fr')->isoFormat('D MMM') }}</span>
                        →
                        <span class="text-white font-semibold capitalize">{{ $pred['eta_max']->locale('fr')->isoFormat('D MMM') }}</span>
                        @if ($pred['buffer_jours'] > 0)
                            <span class="text-amber-300 text-sm">(+{{ $pred['buffer_jours'] }}j marge saturation)</span>
                        @endif
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="rounded-xl bg-white/5 p-4 text-center">
                            <div class="text-2xl font-extrabold text-white">{{ $pred['transit_jours'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase">jours transit</div>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4 text-center">
                            <div class="text-2xl font-extrabold text-white">{{ $pred['risk_arrivee'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase">risque arrivée</div>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4 text-center">
                            <div class="text-2xl font-extrabold text-brand">{{ $pred['confiance'] }}%</div>
                            <div class="text-[10px] text-slate-400 uppercase">confiance</div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- analyse IA --}}
            <section class="glass rounded-2xl p-6">
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">✦</span>
                    Analyse prédictive — LogiMind IA
                </div>
                <p class="mt-4 text-white leading-relaxed">{{ $analyse }}</p>
            </section>
        </div>

        {{-- MAP --}}
        <div class="lg:col-span-2 glass rounded-2xl p-2">
            <div id="map" class="w-full h-[420px] rounded-xl"></div>
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

    L.polyline([origin, dest], { color: '#10e5a4', weight: 3, opacity: 0.7, dashArray: '8 8' }).addTo(map);
    map.fitBounds([origin, dest], { padding: [50, 50] });
</script>
@endpush
