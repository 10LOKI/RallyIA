@extends('layouts.app')
@section('title', 'Suivi live')

@php
    $phases = ['Origine', 'En mer', 'Au port', 'Transit urbain', 'Livré'];
    $current = ['mer' => 1, 'port' => 2, 'terre' => 3, 'livre' => 4][$phase] ?? 0;
@endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand">
                <span class="w-1.5 h-1.5 rounded-full bg-brand animate-pulse"></span> Suivi temps réel · Mer & Ville
            </div>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Suivi du conteneur</h1>
            <p class="text-slate-400 mt-1">{{ $shipment->reference }} · {{ $shipment->marchandise }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="shipment" onchange="this.form.submit()"
                    class="glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($shipments as $s)
                    <option value="{{ $s->id }}" @selected($s->id === $shipment->id) class="bg-ink">{{ $s->reference }} · {{ $s->destination_ville }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- TIMELINE STATUT --}}
    <div class="glass rounded-2xl p-5 mb-6 overflow-x-auto">
        <div class="flex items-center gap-2 min-w-[640px]">
            @foreach ($phases as $i => $p)
                @php $reached = $i <= $current; $isCurrent = $i === $current; @endphp
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full grid place-items-center text-sm font-bold transition
                            {{ $reached ? 'bg-brand text-ink' : 'bg-white/10 text-slate-400' }} {{ $isCurrent ? 'ring-4 ring-brand/30 animate-pulse' : '' }}">
                            {{ ['🏭','🌊','⚓','🚚','📦'][$i] }}
                        </div>
                        <div class="mt-1.5 text-[11px] text-center {{ $reached ? 'text-white' : 'text-slate-500' }}">{{ $p }}</div>
                    </div>
                    @if ($i < count($phases) - 1)
                        <div class="flex-1 h-0.5 rounded {{ $i < $current ? 'bg-brand' : 'bg-white/10' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">
        {{-- MAP --}}
        <div class="lg:col-span-3 glass rounded-2xl p-2 relative">
            <div class="absolute top-4 left-4 z-[1000] flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-full bg-brand/20 text-brand">
                <span class="w-1.5 h-1.5 rounded-full bg-brand animate-pulse"></span>
                {{ $phase === 'terre' ? 'Camion en ville' : ($phase === 'mer' ? 'Navire en mer' : ($phase === 'livre' ? 'Livré' : 'Au port')) }}
            </div>
            <div id="map" class="w-full h-[560px] rounded-xl"></div>
        </div>

        {{-- INFO --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- position live --}}
            <div class="glass glow rounded-2xl p-6">
                <div class="text-xs font-bold uppercase tracking-widest text-brand">Position actuelle</div>
                <div class="mt-3 text-2xl font-extrabold text-white">
                    @if ($phase === 'mer') En mer @elseif ($phase === 'terre') Transit urbain @elseif ($phase === 'livre') {{ $shipment->destination_ville }} @else {{ $port->nom }} @endif
                </div>
                <div class="mt-1 text-sm text-slate-300">{{ $eta }}</div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-white/5 p-3">
                        <div class="text-[10px] text-slate-400 uppercase">Origine</div>
                        <div class="text-sm font-semibold text-white truncate">{{ $shipment->origine }}</div>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <div class="text-[10px] text-slate-400 uppercase">Destination</div>
                        <div class="text-sm font-semibold text-white truncate">{{ $shipment->destination_ville }}</div>
                    </div>
                </div>
            </div>

            {{-- transporteur (navire ou camion) --}}
            @if (in_array($phase, ['mer','port']) && $vessel)
                <div class="glass rounded-2xl p-6">
                    <h3 class="font-bold text-white flex items-center gap-2 mb-4"><span class="text-lg">🚢</span> Navire transporteur</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-400">Nom</span><span class="text-white font-semibold">{{ $vessel['name'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Type</span><span class="text-white">{{ $vessel['type'] ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Vitesse</span><span class="text-white">{{ $vessel['sog'] !== null ? $vessel['sog'].' nœuds' : '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">MMSI</span><span class="text-white">{{ $vessel['mmsi'] }}</span></div>
                    </div>
                </div>
            @elseif ($phase === 'terre')
                <div class="glass rounded-2xl p-6">
                    <h3 class="font-bold text-white flex items-center gap-2 mb-4"><span class="text-lg">🚚</span> Camion transporteur</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-400">Distance restante</span><span class="text-white">{{ $route ? round(($route['distance_km'] ?? 0) * (1 - $landProgress), 1) : '—' }} km</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Progression</span><span class="text-white">{{ (int) round($landProgress * 100) }}%</span></div>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-brand" style="width: {{ (int) round($landProgress * 100) }}%"></div>
                    </div>
                </div>
            @endif

            <div class="text-center text-[11px] text-slate-500">Actualisation auto toutes les 20 s</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { zoomControl: true, attributionControl: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);
    SmartPortTraffic.add(map, { shipOpacity: 0.6, portOpacity: 0.7 }); // trafic maritime ambiant

    const origin = [{{ $origin['lat'] }}, {{ $origin['lng'] }}];
    const port = [{{ $port->lat }}, {{ $port->lng }}];
    const dest = [{{ $shipment->dest_lat }}, {{ $shipment->dest_lng }}];
    const phase = @json($phase);
    const seaProgress = {{ $seaProgress }};
    const landProgress = {{ $landProgress }};
    const routeGeo = @json($route['geometry'] ?? null);
    const seaPath = @json($seaPath); // [[lat,lng], ...] route maritime plausible

    const portIcon = L.divIcon({ html:'<div style="font-size:20px">⚓</div>', className:'', iconSize:[22,22], iconAnchor:[11,11] });
    const homeIcon = L.divIcon({ html:'<div style="font-size:18px">🏭</div>', className:'', iconSize:[20,20], iconAnchor:[10,10] });
    const destIcon = L.divIcon({ html:'<div style="font-size:20px">🏙️</div>', className:'', iconSize:[22,22], iconAnchor:[11,11] });

    L.marker(origin, { icon: homeIcon }).addTo(map).bindPopup('Origine');
    L.marker(port, { icon: portIcon }).addTo(map).bindPopup('{{ $port->nom }}');
    L.marker(dest, { icon: destIcon }).addTo(map).bindPopup('{{ $shipment->destination_ville }}');

    const lerp = (a,b,t) => [a[0]+(b[0]-a[0])*t, a[1]+(b[1]-a[1])*t];

    // Interpole un point a la fraction t le long d'une polyligne [[lat,lng],...]
    function pointAlong(coords, t) {
        if (coords.length < 2) return { point: coords[0], index: 1 };
        let total = 0; const seg = [0];
        for (let i=1;i<coords.length;i++){ total += map.distance(coords[i-1], coords[i]); seg.push(total); }
        const target = total * Math.max(0, Math.min(1, t));
        let idx = seg.findIndex(d => d >= target); if (idx < 1) idx = 1;
        const f = (target - seg[idx-1]) / Math.max(1, seg[idx]-seg[idx-1]);
        return { point: lerp(coords[idx-1], coords[idx], f), index: idx };
    }

    // --- Branche maritime (origine -> port) le long du trajet plausible ---
    const sea = pointAlong(seaPath, seaProgress);
    const seaPoint = sea.point;
    L.polyline(seaPath.slice(0, sea.index).concat([seaPoint]), { color:'#06b6d4', weight:3.5, opacity:.95 }).addTo(map);
    L.polyline([seaPoint].concat(seaPath.slice(sea.index)), { color:'#06b6d4', weight:2.5, opacity:.35, dashArray:'6 8' }).addTo(map);

    // --- Branche terrestre (port -> ville) le long de la route OSRM ---
    let landPoint = port;
    if (routeGeo && routeGeo.coordinates && routeGeo.coordinates.length > 1) {
        const coords = routeGeo.coordinates.map(c => [c[1], c[0]]); // [lat,lng]
        // longueur cumulee
        let total = 0; const seg = [0];
        for (let i=1;i<coords.length;i++){ total += map.distance(coords[i-1], coords[i]); seg.push(total); }
        const target = total * landProgress;
        let idx = seg.findIndex(d => d >= target); if (idx < 1) idx = 1;
        const t = (target - seg[idx-1]) / Math.max(1, seg[idx]-seg[idx-1]);
        landPoint = lerp(coords[idx-1], coords[idx], t);
        // tracé parcouru / restant
        L.polyline(coords.slice(0, idx).concat([landPoint]), { color:'#10e5a4', weight:5, opacity:.95 }).addTo(map);
        L.polyline([landPoint].concat(coords.slice(idx)), { color:'#10e5a4', weight:4, opacity:.3 }).addTo(map);
    } else {
        L.polyline([port, dest], { color:'#10e5a4', weight:3, opacity:.4, dashArray:'6 8' }).addTo(map);
    }

    // --- Marqueur position courante ---
    let cur, emoji, fit;
    if (phase === 'mer') { cur = seaPoint; emoji = '🚢'; fit = seaPath; }
    else if (phase === 'terre') { cur = landPoint; emoji = '🚚'; fit = (routeGeo ? null : [port, dest]); }
    else if (phase === 'livre') { cur = dest; emoji = '📦'; fit = [port, dest]; }
    else { cur = port; emoji = '⚓'; fit = seaPath; }

    const curIcon = L.divIcon({
        html:`<div style="font-size:26px;filter:drop-shadow(0 0 6px rgba(16,229,164,.9))">${emoji}</div>`,
        className:'', iconSize:[28,28], iconAnchor:[14,14]
    });
    L.marker(cur, { icon: curIcon }).addTo(map).bindPopup('Conteneur ici').openPopup();

    if (phase === 'terre' && routeGeo) {
        const g = L.geoJSON(routeGeo).getBounds();
        map.fitBounds(g, { padding:[50,50] });
    } else {
        map.fitBounds(fit, { padding:[60,60] });
    }

    // auto-refresh pour l'effet live
    setTimeout(() => location.reload(), 20000);
</script>
@endpush
