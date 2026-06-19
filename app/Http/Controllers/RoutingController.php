<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\ClaudeService;
use App\Services\RoutingService;
use Illuminate\Http\Request;

class RoutingController extends Controller
{
    // Destinations urbaines proposees
    private array $villes = [
        'Casablanca centre' => [33.5731, -7.5898],
        'Rabat centre'      => [34.0209, -6.8416],
        'Marrakech centre'  => [31.6295, -7.9811],
        'Tanger ville'      => [35.7595, -5.8340],
        'Fes centre'        => [34.0331, -5.0003],
    ];

    public function index(Request $request, RoutingService $routing, ClaudeService $claude)
    {
        $ports = Port::all();
        $portId = (int) $request->query('port', $ports->first()?->id);
        $port = Port::findOrFail($portId);

        $villeNom = $request->query('ville', array_key_first($this->villes));
        if (!isset($this->villes[$villeNom])) {
            $villeNom = array_key_first($this->villes);
        }
        [$destLat, $destLng] = $this->villes[$villeNom];

        $route = $routing->route($port->lat, $port->lng, $destLat, $destLng);

        $conseil = $this->conseil($claude, $port, $villeNom, $route);

        return view('routing', [
            'ports' => $ports,
            'port' => $port,
            'villes' => array_keys($this->villes),
            'villeNom' => $villeNom,
            'dest' => ['lat' => $destLat, 'lng' => $destLng],
            'route' => $route,
            'conseil' => $conseil,
        ]);
    }

    private function conseil(ClaudeService $claude, Port $port, string $ville, ?array $route): string
    {
        $dist = $route['distance_km'] ?? '?';
        $duree = $route['duree_min'] ?? '?';

        $system = "Tu es LogiMind, copilote IA pour chauffeurs de camions au Maroc. "
            . "Tu donnes un conseil de depart et de circulation pour sortir du port et traverser la ville "
            . "sans embouteillages, en economisant temps et carburant. "
            . "Reponds en francais, ton direct et pratique, 3 phrases max, comme un brief chauffeur. Pas de markdown.";

        $prompt = "Trajet: {$port->nom} ({$port->ville}) vers {$ville}. "
            . "Distance {$dist} km, duree estimee {$duree} min. "
            . "Donne: heure de depart conseillee pour eviter les pics de trafic, un axe a privilegier ou eviter, et le benefice (temps/carburant).";

        $mock = "Brief chauffeur : départ conseillé à 5h30 pour sortir de {$port->ville} avant le pic du matin. "
            . "Privilégiez l'autoroute A1/A3 plutôt que les axes urbains saturés en heure de pointe. "
            . "Sur ce trajet de {$dist} km (~{$duree} min), vous gagnez ~25 min et réduisez la conso carburant.";

        return $claude->ask($system, $prompt, $mock);
    }
}
