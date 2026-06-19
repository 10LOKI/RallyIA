@extends('layouts.app')
@section('title', 'Parcours conteneur')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-brand">Vue bout-en-bout · Mer → Terre</div>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Parcours conteneur</h1>
            <p class="text-slate-400 mt-1">Une décision unique, de la mer à la livraison finale</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-400">Conteneur</label>
            <select name="shipment" onchange="this.form.submit()"
                    class="glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($shipments as $s)
                    <option value="{{ $s->id }}" @selected($s->id === $shipment->id) class="bg-ink">{{ $s->reference }} · {{ $s->marchandise }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- BANDEAU DECISION --}}
    <section class="relative overflow-hidden rounded-3xl glass glow p-8 mb-8">
        <div class="absolute -top-24 -right-16 w-80 h-80 bg-brand/20 blur-3xl rounded-full animate-floaty"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div class="max-w-2xl">
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">✦</span>
                    Décision LogiMind
                </div>
                <p class="mt-3 text-2xl font-bold text-white leading-snug">{{ $decision }}</p>
            </div>
            <div class="text-right">
                <div class="text-5xl font-extrabold grad-text tabular-nums">{{ number_format($totalMad, 0, ',', ' ') }}</div>
                <div class="text-sm text-slate-400 uppercase tracking-wide">MAD économisés / conteneur</div>
            </div>
        </div>
    </section>

    <div class="grid lg:grid-cols-5 gap-6">
        {{-- TIMELINE --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- origine --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-white/10 grid place-items-center text-lg shrink-0">🏭</div>
                    <div class="w-0.5 flex-1 bg-gradient-to-b from-white/20 to-brand/40 my-1"></div>
                </div>
                <div class="glass rounded-2xl p-5 flex-1">
                    <div class="text-xs text-slate-400 uppercase tracking-wide">Origine</div>
                    <div class="text-lg font-bold text-white">{{ $shipment->origine }}</div>
                    <div class="text-sm text-slate-400 mt-1">{{ $shipment->marchandise }} · {{ $shipment->reference }}</div>
                </div>
            </div>

            {{-- etape 1 mer --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand/20 grid place-items-center text-lg shrink-0">🌊</div>
                    <div class="w-0.5 flex-1 bg-gradient-to-b from-brand/40 to-brand/40 my-1"></div>
                </div>
                <div class="glass rounded-2xl p-5 flex-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-bold uppercase tracking-wide text-brand">Étape 1 · Optimisation portuaire</div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full" style="background: {{ $best['level']['hex'] }}1a; color: {{ $best['level']['hex'] }}">{{ $best['level']['label'] }}</span>
                    </div>
                    <div class="text-lg font-bold text-white mt-1">{{ $port->nom }}</div>
                    <div class="text-sm text-slate-300 mt-1">Meilleur créneau : <span class="text-white font-semibold capitalize">{{ $best['label_jour'] }}</span></div>
                    <div class="mt-3 flex gap-4 text-sm">
                        <span class="text-brand font-bold">{{ number_format($ecoPort['mad'], 0, ',', ' ') }} MAD</span>
                        <span class="text-slate-400">{{ $ecoPort['heures'] }}h attente évitée</span>
                    </div>
                </div>
            </div>

            {{-- etape 2 terre --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand/20 grid place-items-center text-lg shrink-0">🚚</div>
                    <div class="w-0.5 flex-1 bg-gradient-to-b from-brand/40 to-emerald-400/40 my-1"></div>
                </div>
                <div class="glass rounded-2xl p-5 flex-1">
                    <div class="text-xs font-bold uppercase tracking-wide text-brand">Étape 2 · Fluidité urbaine</div>
                    <div class="text-lg font-bold text-white mt-1">{{ $port->ville }} → {{ $shipment->destination_ville }}</div>
                    <div class="text-sm text-slate-400 mt-1">{{ $route['distance_km'] ?? '—' }} km · {{ $route['duree_min'] ?? '—' }} min</div>
                    <div class="mt-3 flex gap-4 text-sm">
                        <span class="text-brand font-bold">{{ $ecoRoute['mad'] }} MAD</span>
                        <span class="text-slate-400">{{ $ecoRoute['minutes'] }} min · {{ $ecoRoute['litres'] }} L</span>
                    </div>
                </div>
            </div>

            {{-- livraison --}}
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-400/20 grid place-items-center text-lg shrink-0">📦</div>
                </div>
                <div class="glass rounded-2xl p-5 flex-1">
                    <div class="text-xs text-slate-400 uppercase tracking-wide">Livraison finale</div>
                    <div class="text-lg font-bold text-white">{{ $shipment->destination_ville }}</div>
                    <div class="text-sm text-emerald-300 mt-1">Marchandise livrée, coûts minimisés ✓</div>
                </div>
            </div>
        </div>

        {{-- MAP --}}
        <div class="lg:col-span-3 glass rounded-2xl p-2">
            <div id="map" class="w-full h-[560px] rounded-xl"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { zoomControl: true, attributionControl: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    const port = [{{ $port->lat }}, {{ $port->lng }}];
    const dest = [{{ $shipment->dest_lat }}, {{ $shipment->dest_lng }}];

    const portIcon = L.divIcon({ html: '<div style="font-size:22px;filter:drop-shadow(0 0 5px rgba(16,229,164,.7))">⚓</div>', className: '', iconSize: [24,24], iconAnchor: [12,12] });
    const destIcon = L.divIcon({ html: '<div style="font-size:22px;filter:drop-shadow(0 0 5px rgba(244,63,94,.7))">📦</div>', className: '', iconSize: [24,24], iconAnchor: [12,12] });

    L.marker(port, { icon: portIcon }).addTo(map).bindPopup('<b>{{ $port->nom }}</b><br>Étape 1 · Mer');
    L.marker(dest, { icon: destIcon }).addTo(map).bindPopup('<b>{{ $shipment->destination_ville }}</b><br>Livraison');

    const geo = @json($routeJs);
    if (geo) {
        const line = L.geoJSON(geo, { style: { color: '#10e5a4', weight: 5, opacity: 0.9 } }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [60, 60] });
    } else {
        map.fitBounds([port, dest], { padding: [70, 70] });
    }
</script>
@endpush
