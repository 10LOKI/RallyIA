<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\ClaudeService;
use App\Services\CostService;
use App\Services\PortSaturationService;
use App\Services\PredictionService;
use App\Services\SeaRouteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanificationController extends Controller
{
    public function index(
        Request $request,
        PredictionService $prediction,
        PortSaturationService $sat,
        ClaudeService $claude,
        SeaRouteService $seaRoute,
        CostService $costService,
    ) {
        $ports = Port::all();
        $origins = array_keys($prediction->origins());
        $carriers = array_keys($prediction->carriers());

        $originKey = $request->query('origine', $origins[0]);
        if (!in_array($originKey, $origins, true)) {
            $originKey = $origins[0];
        }

        $destId = (int) $request->query('port', $ports->first()?->id);
        $dest = Port::with('conditions')->findOrFail($destId);

        $type = $request->query('type') === 'LCL' ? 'LCL' : 'FCL';
        $routing = $request->query('routing') === 'Direct' ? 'Direct' : 'Transbordement';
        $carrier = in_array($request->query('carrier'), $carriers, true) ? $request->query('carrier') : $carriers[0];

        try {
            $depart = $request->filled('depart')
                ? Carbon::parse($request->query('depart'))
                : Carbon::today();
        } catch (\Throwable) {
            $depart = Carbon::today();
        }

        $forecast = $sat->forecast($dest);
        $pred = $prediction->predict($originKey, $dest, $depart, $type, $routing, $carrier, $forecast['rows']);

        // Estimation du coût (sans IA, affiché immédiatement)
        $cost = $costService->estimate($pred['type'], $pred['transit_base'], $pred['risk_arrivee']);

        // Textes IA différés (chargés en AJAX -> spinners)
        if ($request->boolean('ai')) {
            return response()->json([
                'analyse' => $this->analyse($claude, $dest, $pred),
                'conseils' => $this->conseils($claude, $dest, $pred, $cost),
            ]);
        }
        $analyse = null;
        $conseils = null;

        $seaPath = $seaRoute->path(
            $pred['origin_coords']['lat'],
            $pred['origin_coords']['lng'],
            $dest->lat,
            $dest->lng,
        );

        return view('planification', [
            'ports' => $ports,
            'origins' => $origins,
            'carriers' => $carriers,
            'originKey' => $originKey,
            'dest' => $dest,
            'depart' => $depart,
            'type' => $type,
            'routing' => $routing,
            'carrier' => $carrier,
            'pred' => $pred,
            'analyse' => $analyse,
            'seaPath' => $seaPath,
            'cost' => $cost,
            'conseils' => $conseils,
        ]);
    }

    private function analyse(ClaudeService $claude, Port $dest, array $pred): string
    {
        $fmt = fn (Carbon $d) => $d->locale('fr')->isoFormat('dddd D MMMM');
        $retards = collect($pred['retards'])->map(fn ($r) => "{$r['label']} +{$r['jours']}j ({$r['note']})")->implode(', ');
        $hub = $pred['hub'] ? "via transbordement à {$pred['hub']}" : 'en liaison directe';

        $system = "Tu es SmartPort, copilote logistique IA au Maroc. Tu expliques une prevision d'arrivee de conteneur "
            . "a un client import-export, de façon claire et professionnelle. Francais, 3-4 phrases, pas de markdown.";

        $prompt = "Conteneur {$pred['type']} de {$pred['origin']} vers {$dest->nom}, armateur {$pred['carrier']}, {$hub}. "
            . "Depart {$fmt($pred['depart'])}. Transit estime {$pred['transit_min']}-{$pred['transit_max']} jours.\n"
            . "Arrivee : optimiste {$fmt($pred['eta_optimiste'])}, realiste {$fmt($pred['eta_realiste'])}, pessimiste {$fmt($pred['eta_pessimiste'])}. "
            . "Retards anticipes ({$pred['retard_total']}j) : {$retards}. "
            . "Confiance {$pred['confiance']}%, risque saturation a l'arrivee {$pred['risk_arrivee']}/100.\n"
            . "Explique la date la plus probable, le principal facteur de risque de retard, et un conseil concret pour securiser l'arrivee.";

        $mock = "Pour ce conteneur {$pred['type']} partant de {$pred['origin']} le {$fmt($pred['depart'])} {$hub}, "
            . "l'arrivée la plus probable à {$dest->nom} est le {$fmt($pred['eta_realiste'])} "
            . "(au plus tôt {$fmt($pred['eta_optimiste'])}, au plus tard {$fmt($pred['eta_pessimiste'])}). "
            . "Principal risque de retard : la congestion portuaire à l'arrivée. "
            . "Confiance {$pred['confiance']}% — anticipez le dédouanement pour éviter les surestaries.";

        return $claude->ask($system, $prompt, $mock);
    }

    private function conseils(ClaudeService $claude, Port $dest, array $pred, array $cost): string
    {
        $total = number_format($cost['total_mad'], 0, ',', ' ');

        $system = "Tu es SmartPort, conseiller logistique IA au Maroc. Donne au client des conseils CONCRETS "
            . "pour reduire le cout et avoir une bonne experience d'import. "
            . "Francais, 3 a 4 conseils courts separes par des points, ton pratique, pas de markdown ni de listes a puces.";

        $prompt = "Conteneur {$pred['type']} de {$pred['origin']} vers {$dest->nom}, "
            . ($pred['hub'] ? "transbordement {$pred['hub']}" : "liaison directe") . ", armateur {$pred['carrier']}. "
            . "Cout total estime {$total} MAD (~{$cost['total_usd']} USD). "
            . "Risque saturation a l'arrivee {$pred['risk_arrivee']}/100, surestaries probables {$cost['surestaries']} MAD. "
            . "Donne des conseils pour optimiser le budget (creneau, FCL/LCL, transbordement, dedouanement anticipe, "
            . "negociation, assurance) et reduire le stress.";

        $mock = "Anticipez le dédouanement en préparant tous les documents (B/L, facture, certificat d'origine) "
            . "avant l'arrivée : c'est le 1er poste de retard et de surestaries. "
            . "Si votre volume est inférieur à 15 m³, le groupage (LCL) revient moins cher qu'un conteneur complet. "
            . "Privilégiez une liaison directe quand c'est possible : moins d'escales = moins d'aléas et de frais. "
            . "Visez le créneau d'arrivée à faible saturation recommandé pour éviter ~{$cost['surestaries']} MAD de surestaries, "
            . "et comparez 2-3 armateurs pour négocier le fret et le THC.";

        return $claude->ask($system, $prompt, $mock);
    }
}
