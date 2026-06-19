@extends('layouts.app')
@section('title', 'Fluidité urbaine')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-brand">Étape 2 · Côté terre</div>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Fluidité urbaine</h1>
            <p class="text-slate-400 mt-1">Itinéraire intelligent port → ville, sans embouteillages</p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="port" onchange="this.form.submit()"
                    class="glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($ports as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $port->id) class="bg-ink">{{ $p->nom }}</option>
                @endforeach
            </select>
            <span class="text-slate-500">→</span>
            <select name="ville" onchange="this.form.submit()"
                    class="glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($villes as $v)
                    <option value="{{ $v }}" @selected($v === $villeNom) class="bg-ink">{{ $v }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">
        {{-- MAP --}}
        <div class="lg:col-span-3 glass rounded-2xl p-2 relative">
            <div id="map" class="w-full h-[520px] rounded-xl"></div>
            @if (!empty($route['fallback']))
                <div class="absolute top-4 left-4 z-[1000] text-xs bg-amber-500/20 text-amber-300 px-3 py-1.5 rounded-lg">
                    Tracé approximatif (service routing momentanément indisponible)
                </div>
            @endif
        </div>

        {{-- PANEL --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="glass rounded-2xl p-5">
                    <div class="text-slate-400 text-sm">Distance</div>
                    <div class="text-3xl font-extrabold text-white mt-1">{{ $route['distance_km'] ?? '—' }}<span class="text-lg text-slate-400 ml-1">km</span></div>
                </div>
                <div class="glass rounded-2xl p-5">
                    <div class="text-slate-400 text-sm">Durée estimée</div>
                    <div class="text-3xl font-extrabold text-white mt-1">{{ $route['duree_min'] ?? '—' }}<span class="text-lg text-slate-400 ml-1">min</span></div>
                </div>
            </div>

            {{-- brief IA --}}
            <div class="glass glow rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -top-16 -right-10 w-56 h-56 bg-brand/15 blur-3xl rounded-full"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-brand font-bold text-sm">
                        <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">🚚</span>
                        Brief chauffeur · LogiMind IA
                    </div>
                    <p class="mt-4 text-white leading-relaxed">{{ $conseil }}</p>
                </div>
            </div>

            {{-- ROI --}}
            <div class="glass glow rounded-2xl p-6">
                <div class="text-xs font-bold uppercase tracking-widest text-brand mb-4">Économies vs trajet non optimisé</div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-2xl font-extrabold text-white tabular-nums">{{ $economies['minutes'] }}</div>
                        <div class="text-[10px] text-slate-400 uppercase">min gagnées</div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-white tabular-nums">{{ $economies['litres'] }}</div>
                        <div class="text-[10px] text-slate-400 uppercase">L gasoil</div>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold text-brand tabular-nums">{{ $economies['mad'] }}</div>
                        <div class="text-[10px] text-slate-400 uppercase">MAD</div>
                    </div>
                </div>
            </div>

            {{-- trajet --}}
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-brand"></span>
                    <div>
                        <div class="text-xs text-slate-400">Départ</div>
                        <div class="font-semibold text-white">{{ $port->nom }}</div>
                    </div>
                </div>
                <div class="ml-1.5 my-1 border-l-2 border-dashed border-white/15 h-6"></div>
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                    <div>
                        <div class="text-xs text-slate-400">Destination</div>
                        <div class="font-semibold text-white">{{ $villeNom }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { zoomControl: true, attributionControl: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    const start = [{{ $port->lat }}, {{ $port->lng }}];
    const end = [{{ $dest['lat'] }}, {{ $dest['lng'] }}];

    const startIcon = L.divIcon({ html: '<div style="width:16px;height:16px;border-radius:50%;background:#10e5a4;box-shadow:0 0 0 4px rgba(16,229,164,.3)"></div>', className: '', iconSize: [16,16] });
    const endIcon = L.divIcon({ html: '<div style="width:16px;height:16px;border-radius:50%;background:#f43f5e;box-shadow:0 0 0 4px rgba(244,63,94,.3)"></div>', className: '', iconSize: [16,16] });

    L.marker(start, { icon: startIcon }).addTo(map).bindPopup('<b>{{ $port->nom }}</b>');
    L.marker(end, { icon: endIcon }).addTo(map).bindPopup('<b>{{ $villeNom }}</b>');

    const geo = @json($route['geometry'] ?? null);
    if (geo) {
        const line = L.geoJSON(geo, { style: { color: '#10e5a4', weight: 5, opacity: 0.9 } }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [50, 50] });
    } else {
        map.fitBounds([start, end], { padding: [60, 60] });
    }

    // Navires AIS autour du port de depart
    const vessels = @json($nearbyJs);
    vessels.forEach(v => {
        if (!v.lat || !v.lng) return;
        const moving = (v.sog ?? 0) > 0.5;
        const icon = L.divIcon({
            className: '',
            html: `<div style="font-size:16px; filter: drop-shadow(0 0 3px rgba(16,229,164,.5))">${moving ? '🚢' : '⚓'}</div>`,
            iconSize: [18, 18], iconAnchor: [9, 9],
        });
        L.marker([v.lat, v.lng], { icon }).addTo(map).bindPopup(
            `<b>${v.name ?? 'Navire'}</b><br>${v.type ?? ''}${v.dest ? '<br>→ '+v.dest : ''}${v.sog != null ? '<br>'+v.sog+' nœuds' : ''}`
        );
    });
</script>
@endpush
