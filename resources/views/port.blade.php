@extends('layouts.app')
@section('title', 'Optimisation portuaire')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-brand">Étape 1 · Côté mer</div>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Optimisation portuaire</h1>
            <p class="text-slate-400 mt-1">Prévision de saturation & meilleur créneau d'arrivée</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-400">Port</label>
            <select name="port" onchange="this.form.submit()"
                    class="glass rounded-xl px-4 py-2.5 text-white bg-panel border border-white/10 focus:outline-none focus:ring-2 focus:ring-brand">
                @foreach ($ports as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $port->id) class="bg-ink">{{ $p->nom }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- RECO IA --}}
    @php $best = $forecast['best']; @endphp
    <section class="glass glow rounded-3xl p-8 mb-8 relative overflow-hidden">
        <div class="absolute -top-20 -right-10 w-72 h-72 bg-brand/15 blur-3xl rounded-full"></div>
        <div class="relative grid md:grid-cols-3 gap-8">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">✦</span>
                    Recommandation LogiMind IA
                </div>
                <p class="mt-4 text-xl text-white leading-relaxed font-medium">{{ $reco }}</p>
            </div>
            <div class="md:border-l md:border-white/10 md:pl-8 flex flex-col justify-center">
                <div class="text-sm text-slate-400">Meilleur créneau</div>
                <div class="text-2xl font-extrabold text-white capitalize mt-1">{{ $best['label_jour'] }}</div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full" style="background: {{ $best['level']['hex'] }}1a; color: {{ $best['level']['hex'] }}">
                        Risque {{ $best['level']['label'] }}
                    </span>
                    <span class="text-slate-400 text-sm">{{ $best['risk'] }}/100</span>
                </div>
            </div>
        </div>
    </section>

    <div class="grid lg:grid-cols-5 gap-6">
        {{-- MAP --}}
        <div class="lg:col-span-2 glass rounded-2xl p-2">
            <div id="map" class="w-full h-[420px] rounded-xl"></div>
        </div>

        {{-- FORECAST 7J --}}
        <div class="lg:col-span-3 glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-white">Prévisions 7 jours</h3>
                <div class="flex items-center gap-4 text-xs text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>Faible</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Modéré</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>Élevé</span>
                </div>
            </div>

            {{-- bar chart --}}
            <div class="flex items-end justify-between gap-3 h-48 px-1">
                @foreach ($forecast['rows'] as $r)
                    <div class="flex-1 flex flex-col items-center gap-2 group">
                        <div class="text-xs font-bold text-white opacity-0 group-hover:opacity-100 transition">{{ $r['risk'] }}</div>
                        <div class="w-full rounded-t-lg transition-all duration-500 relative"
                             style="height: {{ max(6, $r['risk']) }}%; background: {{ $r['level']['hex'] }}; min-height:6px"
                             title="Risque {{ $r['risk'] }}/100">
                            @if ($r['date']->isSameDay($best['date']))
                                <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-brand text-lg">★</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-400 text-center capitalize leading-tight">{{ $r['label_jour'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- detail rows --}}
            <div class="mt-6 space-y-2">
                @foreach ($forecast['rows'] as $r)
                    <div class="flex items-center gap-4 text-sm py-2 px-3 rounded-lg {{ $r['date']->isSameDay($best['date']) ? 'bg-brand/10' : 'hover:bg-white/5' }}">
                        <div class="w-24 text-slate-300 capitalize">{{ $r['label_jour'] }}</div>
                        <div class="flex-1 flex items-center gap-4 text-xs text-slate-400">
                            <span>🌊 Sat. {{ $r['saturation_pct'] }}%</span>
                            <span>⛅ Météo {{ $r['meteo_score'] }}/100</span>
                            <span>📈 Éco {{ $r['news_sentiment'] > 0 ? '+' : '' }}{{ $r['news_sentiment'] }}</span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full" style="background: {{ $r['level']['hex'] }}1a; color: {{ $r['level']['hex'] }}">{{ $r['risk'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- NEWS / CONTEXTE ECO --}}
    @php
        $s = $sentiment['score'];
        $sLevel = $s >= 15 ? ['Favorable','#10e5a4'] : ($s <= -15 ? ['Défavorable','#f43f5e'] : ['Neutre','#f59e0b']);
    @endphp
    <section class="mt-6 grid lg:grid-cols-5 gap-6">
        <div class="lg:col-span-2 glass rounded-2xl p-6 flex flex-col">
            <div class="flex items-center gap-2 text-brand font-bold text-sm">
                <span class="w-7 h-7 rounded-lg bg-brand/15 grid place-items-center">📈</span>
                Contexte économique — IA
            </div>
            <div class="mt-5 flex items-end gap-3">
                <div class="text-4xl font-extrabold tabular-nums" style="color: {{ $sLevel[1] }}">{{ $s > 0 ? '+' : '' }}{{ $s }}</div>
                <span class="mb-1.5 text-xs font-bold px-2.5 py-1 rounded-full" style="background: {{ $sLevel[1] }}1a; color: {{ $sLevel[1] }}">{{ $sLevel[0] }}</span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ ($s + 100) / 2 }}%; background: {{ $sLevel[1] }}"></div>
            </div>
            <p class="mt-4 text-slate-300 leading-relaxed text-sm">{{ $sentiment['resume'] }}</p>
            <p class="mt-auto pt-4 text-[11px] text-slate-500">Dérivé de l'actualité économique en temps réel · injecté dans le score de risque du jour.</p>
        </div>

        <div class="lg:col-span-3 glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-white">Actualité logistique & économique — Maroc</h3>
                <span class="text-[10px] uppercase tracking-widest text-slate-400">NewsAPI</span>
            </div>
            <div class="space-y-1">
                @foreach ($headlines as $h)
                    <a href="{{ $h['url'] }}" target="_blank" rel="noopener"
                       class="flex items-start gap-3 py-3 px-3 rounded-lg hover:bg-white/5 transition group border-b border-white/5 last:border-0">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-brand shrink-0"></span>
                        <div class="min-w-0">
                            <div class="text-sm text-slate-200 group-hover:text-white leading-snug">{{ $h['title'] }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $h['source'] }}
                                @if (!empty($h['publishedAt']))
                                    · {{ \Carbon\Carbon::parse($h['publishedAt'])->locale('fr')->diffForHumans() }}
                                @endif
                            </div>
                        </div>
                        <span class="ml-auto text-slate-600 group-hover:text-brand transition shrink-0">↗</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { zoomControl: true, attributionControl: false }).setView([{{ $port->lat }}, {{ $port->lng }}], 9);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    const color = '{{ $best['level']['hex'] }}';
    L.circle([{{ $port->lat }}, {{ $port->lng }}], {
        radius: 12000, color: color, fillColor: color, fillOpacity: 0.18, weight: 2
    }).addTo(map);

    L.marker([{{ $port->lat }}, {{ $port->lng }}]).addTo(map)
        .bindPopup('<b>{{ $port->nom }}</b><br>{{ $port->ville }}').openPopup();
</script>
@endpush
