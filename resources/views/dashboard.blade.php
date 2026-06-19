@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
    {{-- HERO --}}
    <section class="relative overflow-hidden rounded-3xl glass glow p-10 mb-10">
        <div class="absolute -top-24 -right-24 w-80 h-80 bg-brand/20 blur-3xl rounded-full animate-floaty"></div>
        <div class="relative max-w-3xl">
            <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-brand bg-brand/10 px-3 py-1 rounded-full">
                Intelligence artificielle logistique
            </span>
            <h1 class="mt-5 text-4xl md:text-5xl font-extrabold text-white leading-[1.1]">
                De la <span class="grad-text">mer</span> à la <span class="grad-text">ville</span>,<br>
                votre marchandise sans friction.
            </h1>
            <p class="mt-5 text-slate-300 text-lg leading-relaxed">
                SmartPort transforme météo marine, contexte économique et trafic urbain en décisions simples :
                <em>partez à ce moment, prenez cette route, économisez temps et argent.</em>
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('port') }}" class="px-5 py-3 rounded-xl bg-gradient-to-r from-brand to-brand-deep text-ink font-bold hover:opacity-90 transition">
                    ⚓ Optimiser un port
                </a>
                <a href="{{ route('routing') }}" class="px-5 py-3 rounded-xl border border-white/15 text-white font-semibold hover:bg-white/5 transition">
                    🚚 Planifier un trajet
                </a>
            </div>
        </div>
    </section>

    {{-- KPIs PORTS --}}
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-white">État des ports — aujourd'hui</h2>
        <span class="flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-full {{ $vesselsLive ? 'bg-brand/15 text-brand' : 'bg-amber-500/15 text-amber-300' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $vesselsLive ? 'bg-brand animate-pulse' : 'bg-amber-300' }}"></span>
            🚢 {{ $vesselsTotal }} navires {{ $vesselsLive ? 'en direct (AIS)' : '(démo)' }}
        </span>
    </div>
    <div class="grid md:grid-cols-3 gap-5 mb-12">
        @foreach ($stats as $s)
            <div class="glass rounded-2xl p-6 hover:glow transition group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-bold text-white">{{ $s['port']->nom }}</div>
                        <div class="text-sm text-slate-400">{{ $s['port']->ville }}</div>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                          style="background: {{ $s['level']['hex'] }}1a; color: {{ $s['level']['hex'] }}">
                        {{ $s['level']['label'] }}
                    </span>
                </div>

                <div class="mt-6 flex items-end gap-3">
                    <div class="text-4xl font-extrabold text-white tabular-nums">{{ $s['risk'] }}</div>
                    <div class="text-slate-400 text-sm mb-1.5">/ 100 risque</div>
                </div>

                <div class="mt-3 h-2 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full transition-all" style="width: {{ $s['risk'] }}%; background: {{ $s['level']['hex'] }}"></div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-white/5 py-2">
                        <div class="text-sm font-bold text-white">{{ $s['saturation'] }}%</div>
                        <div class="text-[10px] text-slate-400">Saturation</div>
                    </div>
                    <div class="rounded-lg bg-white/5 py-2">
                        <div class="text-sm font-bold text-white">{{ $s['vessels'] }}</div>
                        <div class="text-[10px] text-slate-400">Navires</div>
                    </div>
                    <div class="rounded-lg bg-white/5 py-2">
                        <div class="text-sm font-bold text-white">{{ $s['arrivals'] }}</div>
                        <div class="text-[10px] text-slate-400">Arrivées</div>
                    </div>
                </div>
                <div class="mt-3 text-right">
                    <a href="{{ route('port', ['port' => $s['port']->id]) }}" class="text-xs text-brand font-semibold opacity-0 group-hover:opacity-100 transition">Détails →</a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- PROCESS 2 ETAPES --}}
    <div class="grid md:grid-cols-2 gap-5 mb-12">
        <div class="glass rounded-2xl p-7">
            <div class="text-3xl mb-3">🌊</div>
            <div class="text-xs font-bold uppercase tracking-widest text-brand">Étape 1 · Côté mer</div>
            <h3 class="mt-2 text-xl font-bold text-white">Optimisation portuaire</h3>
            <p class="mt-2 text-slate-300 leading-relaxed">
                L'IA croise météo marine, actualité économique mondiale et flux logistiques pour prédire
                la saturation des grands ports (Tanger Med, Casablanca) et recommander le créneau idéal.
            </p>
            <a href="{{ route('port') }}" class="mt-4 inline-block text-brand font-semibold">Voir les prévisions →</a>
        </div>
        <div class="glass rounded-2xl p-7">
            <div class="text-3xl mb-3">🏙️</div>
            <div class="text-xs font-bold uppercase tracking-widest text-brand">Étape 2 · Côté terre</div>
            <h3 class="mt-2 text-xl font-bold text-white">Fluidité urbaine</h3>
            <p class="mt-2 text-slate-300 leading-relaxed">
                Une fois au port, SmartPort analyse le trafic et génère un itinéraire intelligent pour les
                chauffeurs — sortir du port et traverser la ville sans embouteillages.
            </p>
            <a href="{{ route('routing') }}" class="mt-4 inline-block text-brand font-semibold">Tracer un itinéraire →</a>
        </div>
    </div>

    {{-- SHIPMENTS --}}
    <h2 class="text-xl font-bold text-white mb-5">Conteneurs suivis</h2>
    <div class="glass rounded-2xl overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-left text-slate-400 border-b border-white/10">
                <tr>
                    <th class="px-6 py-4 font-medium">Référence</th>
                    <th class="px-6 py-4 font-medium">Marchandise</th>
                    <th class="px-6 py-4 font-medium">Origine → Destination</th>
                    <th class="px-6 py-4 font-medium">Port</th>
                    <th class="px-6 py-4 font-medium">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shipments as $sh)
                    @php
                        $badge = match($sh->statut) {
                            'en_mer' => ['Au large', '#06b6d4'],
                            'au_port' => ['Au port', '#f59e0b'],
                            'en_route' => ['En route', '#10e5a4'],
                            default => ['Livré', '#94a3b8'],
                        };
                    @endphp
                    <tr class="border-b border-white/5 hover:bg-white/5 transition">
                        <td class="px-6 py-4 font-semibold text-white">{{ $sh->reference }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $sh->marchandise }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $sh->origine }} <span class="text-slate-500">→</span> {{ $sh->destination_ville }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $sh->port->nom }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full" style="background: {{ $badge[1] }}1a; color: {{ $badge[1] }}">{{ $badge[0] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
