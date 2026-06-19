<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\ClaudeService;
use App\Services\NewsService;
use App\Services\PortSaturationService;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(
        Request $request,
        PortSaturationService $sat,
        ClaudeService $claude,
        NewsService $news,
    ) {
        $ports = Port::all();
        $selectedId = (int) $request->query('port', $ports->first()?->id);
        $port = Port::with('conditions')->findOrFail($selectedId);

        // News reelles + sentiment eco (NewsAPI -> Claude)
        $headlines = $news->headlines();
        $sentiment = $news->sentiment($headlines);

        $forecast = $sat->forecast($port);

        // Injecte le sentiment news REEL dans la condition du jour (1ere ligne) + recalcule risque
        if (!empty($forecast['rows'])) {
            $r0 = &$forecast['rows'][0];
            $r0['news_sentiment'] = $sentiment['score'];
            $r0['risk'] = $sat->riskValues($r0['saturation_pct'], $r0['meteo_score'], $sentiment['score']);
            $r0['level'] = $sat->level($r0['risk']);
            unset($r0);
            // recalcule le meilleur creneau apres mise a jour
            $forecast['best'] = collect($forecast['rows'])->sortBy('risk')->first();
        }

        $best = $forecast['best'];
        $reco = $this->reco($claude, $port, $best, $forecast['rows'], $sentiment);

        return view('port', [
            'ports' => $ports,
            'port' => $port,
            'forecast' => $forecast,
            'reco' => $reco,
            'headlines' => $headlines,
            'sentiment' => $sentiment,
        ]);
    }

    private function reco(ClaudeService $claude, Port $port, array $best, array $rows, array $sentiment): string
    {
        $resume = collect($rows)->map(fn ($r) =>
            "{$r['label_jour']}: saturation {$r['saturation_pct']}%, meteo {$r['meteo_score']}/100, sentiment eco {$r['news_sentiment']}, risque {$r['risk']}/100"
        )->implode("\n");

        $system = "Tu es LogiMind, copilote logistique IA pour l'import-export au Maroc. "
            . "Tu conseilles importateurs/exportateurs sur le meilleur creneau pour faire arriver/partir des conteneurs, "
            . "en evitant saturation portuaire, mauvaise meteo marine et contexte economique defavorable. "
            . "Reponds en francais, ton professionnel et concret, 3-4 phrases max. Pas de markdown.";

        $prompt = "Port: {$port->nom} ({$port->ville}).\n"
            . "Contexte economique actuel (actualite reelle): {$sentiment['resume']} (sentiment {$sentiment['score']}/100).\n"
            . "Previsions 7 jours:\n{$resume}\n\n"
            . "Le meilleur creneau identifie est {$best['label_jour']} (risque {$best['risk']}/100). "
            . "Donne une recommandation claire: quel jour viser, pourquoi, et quel benefice concret (eviter frais de stockage / retards).";

        $mock = "Recommandation LogiMind : visez une arrivée le {$best['label_jour']} sur {$port->nom}. "
            . "Le risque de saturation y est le plus bas ({$best['risk']}/100), avec une fenêtre météo favorable. "
            . "Contexte économique : {$sentiment['resume']} "
            . "En ciblant ce créneau, vous évitez les files d'attente au mouillage et réduisez les frais de surestaries.";

        return $claude->ask($system, $prompt, $mock);
    }
}
