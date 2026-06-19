<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Vessel;
use App\Services\RoutingService;
use App\Services\SeaRouteService;
use App\Services\VesselService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /** Coords des villes d'origine (pour la branche maritime). */
    private array $geo = [
        'Shanghai'  => [31.2304, 121.4737],
        'Ningbo'    => [29.8683, 121.5440],
        'Shenzhen'  => [22.5431, 114.0579],
        'Istanbul'  => [41.0082, 28.9784],
        'Valence'   => [39.4699, -0.3763],
        'Rotterdam' => [51.9244, 4.4777],
        'Marseille' => [43.2965, 5.3698],
    ];

    public function index(Request $request, RoutingService $routing, VesselService $vessels, SeaRouteService $seaRoute)
    {
        $shipments = Shipment::with('port')->get();
        $selectedId = (int) $request->query('shipment', $shipments->first()?->id);
        $shipment = Shipment::with('port')->findOrFail($selectedId);
        $port = $shipment->port;

        $origin = $this->geocode($shipment->origine, $port);

        // Trajet maritime plausible (origine -> port) qui suit les mers
        $seaPath = $seaRoute->path($origin[0], $origin[1], $port->lat, $port->lng);

        // Branche terrestre : port -> ville (OSRM)
        $route = $routing->route($port->lat, $port->lng, $shipment->dest_lat, $shipment->dest_lng);

        // Phase + progression selon le statut (avec micro-derive temporelle = effet "live")
        [$phase, $seaProgress, $landProgress] = $this->progress($shipment);

        // Navire associe (AIS reel si dispo, sinon mock proche du port)
        $vessel = $this->pickVessel($vessels, $port);

        // ETA texte
        $eta = $this->etaText($phase, $route, $landProgress);

        return view('tracking', [
            'shipments' => $shipments,
            'shipment' => $shipment,
            'port' => $port,
            'origin' => ['lat' => $origin[0], 'lng' => $origin[1]],
            'seaPath' => $seaPath,
            'route' => $route,
            'phase' => $phase,
            'seaProgress' => $seaProgress,
            'landProgress' => $landProgress,
            'vessel' => $vessel,
            'eta' => $eta,
        ]);
    }

    private function geocode(string $origine, $port): array
    {
        foreach ($this->geo as $ville => $coords) {
            if (stripos($origine, $ville) !== false) {
                return $coords;
            }
        }
        // defaut : large au nord-ouest du port
        return [$port->lat + 6, $port->lng - 10];
    }

    /** @return array{0:string,1:float,2:float} [phase, seaProgress, landProgress] */
    private function progress(Shipment $shipment): array
    {
        // micro-derive deterministe (bouge un peu a chaque minute) pour l'effet live
        $drift = (now()->timestamp % 600) / 600 * 0.12; // 0..0.12

        return match ($shipment->statut) {
            'en_mer'  => ['mer', min(0.95, 0.45 + $drift), 0.0],
            'au_port' => ['port', 1.0, 0.0],
            'en_route' => ['terre', 1.0, min(0.95, 0.4 + $drift)],
            default    => ['livre', 1.0, 1.0],
        };
    }

    private function pickVessel(VesselService $vessels, $port): ?array
    {
        $v = $vessels->nearPort($port, 90)->first() ?? $vessels->recent()->first();
        if (!$v instanceof Vessel) {
            return null;
        }
        return [
            'name' => $v->name,
            'type' => $v->ship_type,
            'sog' => $v->sog,
            'status' => $v->nav_status,
            'mmsi' => $v->mmsi,
        ];
    }

    private function lerp(array $a, array $b, float $t): array
    {
        return [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t];
    }

    private function etaText(string $phase, ?array $route, float $landProgress): string
    {
        return match ($phase) {
            'mer'   => 'Arrivée au port estimée sous 3–6 jours',
            'port'  => 'Au port — en attente de chargement camion',
            'terre' => $route
                ? 'Livraison dans ~' . max(1, (int) round(($route['duree_min'] ?? 60) * (1 - $landProgress))) . ' min'
                : 'En transit urbain',
            default => 'Conteneur livré ✓',
        };
    }
}
