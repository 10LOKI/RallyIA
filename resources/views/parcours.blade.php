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
                    Décision SmartPort
                </div>
                <p data-ai="decision" data-ai-loading="{{ $decision === null ? '1' : '0' }}" class="mt-3 text-2xl font-bold text-white leading-snug">@if($decision !== null){{ $decision }}@else<span class="text-base font-normal text-slate-400 inline-flex items-center gap-2"><span class="ai-spin"></span> SmartPort calcule la décision optimale…</span>@endif</p>
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
                    <div class="w-0.5 flex-1 bg-white/15 my-1"></div>
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
                    <div class="w-0.5 flex-1 bg-brand/40 my-1"></div>
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
                    <div class="w-0.5 flex-1 bg-brand/40 my-1"></div>
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
    SmartPortTraffic.add(map, { shipOpacity: 0.55, portOpacity: 0.65 }); // trafic maritime ambiant

    const origin = [{{ $origin[0] }}, {{ $origin[1] }}];
    const port = [{{ $port->lat }}, {{ $port->lng }}];
    const dest = [{{ $shipment->dest_lat }}, {{ $shipment->dest_lng }}];
    const seaPath = @json($seaPath);

    const homeIcon = L.divIcon({ html: '<div style="font-size:20px">🏭</div>', className: '', iconSize: [22,22], iconAnchor: [11,11] });
    const portIcon = L.divIcon({ html: '<div style="font-size:22px;filter:drop-shadow(0 0 5px rgba(16,229,164,.7))">⚓</div>', className: '', iconSize: [24,24], iconAnchor: [12,12] });
    const destIcon = L.divIcon({ html: '<div style="font-size:22px;filter:drop-shadow(0 0 5px rgba(244,63,94,.7))">📦</div>', className: '', iconSize: [24,24], iconAnchor: [12,12] });

    // Étape 1 — branche maritime (origine -> port)
    L.polyline(seaPath, { color: '#06b6d4', weight: 3, opacity: 0.85 }).addTo(map);
    L.marker(origin, { icon: homeIcon }).addTo(map).bindPopup('<b>{{ $shipment->origine }}</b><br>Origine');
    L.marker(port, { icon: portIcon }).addTo(map).bindPopup('<b>{{ $port->nom }}</b><br>Arrivée port · Étape 1');
    L.marker(dest, { icon: destIcon }).addTo(map).bindPopup('<b>{{ $shipment->destination_ville }}</b><br>Livraison · Étape 2');

    // Étape 2 — branche terrestre (port -> ville)
    const geo = @json($routeJs);
    const all = seaPath.slice();
    if (geo) {
        const line = L.geoJSON(geo, { style: { color: '#10e5a4', weight: 5, opacity: 0.9 } }).addTo(map);
        line.getBounds() && map.fitBounds(L.latLngBounds(seaPath).extend(line.getBounds()), { padding: [50, 50] });
    } else {
        L.polyline([port, dest], { color: '#10e5a4', weight: 3, opacity: 0.5, dashArray: '6 8' }).addTo(map);
        all.push(dest);
        map.fitBounds(all, { padding: [60, 60] });
    }
</script>
@endpush
