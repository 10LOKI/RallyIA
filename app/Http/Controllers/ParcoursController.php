<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ClaudeService;
use App\Services\PortSaturationService;
use App\Services\RoutingService;
use App\Services\SavingsService;
use Illuminate\Http\Request;

class ParcoursController extends Controller
{
    public function index(
        Request $request,
        PortSaturationService $sat,
        RoutingService $routing,
        SavingsService $savings,
        ClaudeService $claude,
    ) {
        $shipments = Shipment::with('port')->get();
        $selectedId = (int) $request->query('shipment', $shipments->first()?->id);
        $shipment = Shipment::with('port')->findOrFail($selectedId);
        $port = $shipment->port;

        // Etape 1 — mer : meilleur creneau + economies portuaires
        $forecast = $sat->forecast($port);
        $best = $forecast['best'];
        $ecoPort = $savings->port($forecast['rows'], $best);

        // Etape 2 — terre : itineraire port -> ville + economies trajet
        $route = $routing->route($port->lat, $port->lng, $shipment->dest_lat, $shipment->dest_lng);
        $ecoRoute = $savings->route($route['distance_km'] ?? 0, $route['duree_min'] ?? 0);

        $totalMad = $ecoPort['mad'] + $ecoRoute['mad'];

        $decision = $this->decision($claude, $shipment, $best, $route, $totalMad);

        $routeJs = $route['geometry'] ?? null;

        return view('parcours', compact(
            'shipments', 'shipment', 'port', 'forecast', 'best',
            'ecoPort', 'route', 'ecoRoute', 'totalMad', 'decision', 'routeJs'
        ));
    }

    private function decision(ClaudeService $claude, Shipment $shipment, array $best, ?array $route, int $totalMad): string
    {
        $ville = $shipment->destination_ville;
        $km = $route['distance_km'] ?? '?';

        $system = "Tu es LogiMind, copilote logistique IA au Maroc. Resume la decision optimale pour un conteneur "
            . "en UNE phrase percutante, format: 'Partez [quand], route vers [ville], economisez [montant].' "
            . "Francais, direct, pas de markdown.";

        $prompt = "Conteneur {$shipment->reference} ({$shipment->marchandise}), origine {$shipment->origine}, "
            . "arrivee port {$shipment->port->nom} au meilleur creneau {$best['label_jour']}, "
            . "puis route vers {$ville} ({$km} km). Economie totale estimee {$totalMad} MAD. "
            . "Donne la phrase de decision.";

        $mock = "Arrivée {$best['label_jour']} à {$shipment->port->nom}, puis route directe vers {$ville} — "
            . "économisez ~" . number_format($totalMad, 0, ',', ' ') . " MAD sur ce conteneur.";

        return $claude->ask($system, $prompt, $mock);
    }
}
