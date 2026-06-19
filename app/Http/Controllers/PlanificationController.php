<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\ClaudeService;
use App\Services\PortSaturationService;
use App\Services\PredictionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanificationController extends Controller
{
    public function index(
        Request $request,
        PredictionService $prediction,
        PortSaturationService $sat,
        ClaudeService $claude,
    ) {
        $ports = Port::all();
        $origins = array_keys($prediction->origins());

        $originKey = $request->query('origine', $origins[0]);
        if (!in_array($originKey, $origins, true)) {
            $originKey = $origins[0];
        }

        $destId = (int) $request->query('port', $ports->first()?->id);
        $dest = Port::with('conditions')->findOrFail($destId);

        try {
            $depart = $request->filled('depart')
                ? Carbon::parse($request->query('depart'))
                : Carbon::today();
        } catch (\Throwable) {
            $depart = Carbon::today();
        }

        $forecast = $sat->forecast($dest);
        $pred = $prediction->predict($originKey, $dest, $depart, $forecast['rows']);

        $analyse = $this->analyse($claude, $originKey, $dest, $pred);

        return view('planification', [
            'ports' => $ports,
            'origins' => $origins,
            'originKey' => $originKey,
            'dest' => $dest,
            'depart' => $depart,
            'pred' => $pred,
            'analyse' => $analyse,
        ]);
    }

    private function analyse(ClaudeService $claude, string $origin, Port $dest, array $pred): string
    {
        $fmt = fn (Carbon $d) => $d->locale('fr')->isoFormat('dddd D MMMM');

        $system = "Tu es LogiMind, copilote logistique IA au Maroc. Tu expliques une prevision d'arrivee de conteneur "
            . "a un client import-export. Reponds en francais, 2-3 phrases, concret, pas de markdown.";

        $prompt = "Conteneur partant de {$origin} le {$fmt($pred['depart'])} vers {$dest->nom}. "
            . "Transit maritime estime: {$pred['transit_jours']} jours. "
            . "Arrivee prevue: {$fmt($pred['eta_base'])}, fenetre realiste jusqu'au {$fmt($pred['eta_max'])} "
            . "(risque saturation a l'arrivee {$pred['risk_arrivee']}/100, confiance {$pred['confiance']}%). "
            . "Explique la prevision et conseille si ce creneau de depart est bon ou s'il faut l'ajuster.";

        $mock = "Avec un départ de {$origin} le {$fmt($pred['depart'])}, votre conteneur arriverait à {$dest->nom} "
            . "vers le {$fmt($pred['eta_base'])} ({$pred['transit_jours']} jours de transit). "
            . "Compte tenu du risque de saturation à l'arrivée ({$pred['risk_arrivee']}/100), prévoyez une marge "
            . "jusqu'au {$fmt($pred['eta_max'])}. Confiance de la prévision : {$pred['confiance']}%.";

        return $claude->ask($system, $prompt, $mock);
    }
}
