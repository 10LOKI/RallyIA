<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\ClaudeService;
use App\Services\NewsService;
use App\Services\PortSaturationService;
use App\Services\SavingsService;
use App\Services\VesselService;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(
        Request $request,
        PortSaturationService $sat,
        ClaudeService $claude,
        NewsService $news,
        VesselService $vessels,
        SavingsService $savings,
    ) {
        $ports = Port::all();
        $selectedId = (int) $request->query('port', $ports->first()?->id);
        $port = Port::with('conditions')->findOrFail($selectedId);

        // News reelles + sentiment eco (NewsAPI -> Claude)
        $headlines = $news->headlines();
        $sentiment = $news->sentiment($headlines);

        // Navires AIS (reel si flux actif, sinon mock)
        $nearby = $vessels->nearPort($port, 70);
        $arrivals = $vessels->expectedArrivals($port);
        $vesselsLive = $vessels->isLive();

        $nearbyJs = $nearby->map(fn ($v) => [
            'name' => $v->name,
            'lat' => $v->lat,
            'lng' => $v->lng,
            'type' => $v->ship_type,
            'dest' => $v->destination,
            'sog' => $v->sog,
            'status' => $v->nav_status,
        ])->values();

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
        $economies = $savings->port($forecast['rows'], $best);

        // Evaluation temps reel EXPLICABLE (aujourd'hui) : 4 facteurs dont navires AIS
        $today = $forecast['rows'][0] ?? null;
        $navalPressure = $sat->navalPressure($arrivals->count(), $nearby->count());
        $assessment = $sat->assess(
            $today['saturation_pct'] ?? 0,
            $today['meteo_score'] ?? 100,
            $sentiment['score'],
            $navalPressure,
        );

        $reco = $this->reco($claude, $port, $best, $forecast['rows'], $sentiment, $arrivals->count(), $economies, $assessment);

        return view('port', [
            'ports' => $ports,
            'port' => $port,
            'forecast' => $forecast,
            'reco' => $reco,
            'headlines' => $headlines,
            'sentiment' => $sentiment,
            'assessment' => $assessment,
            'navalPressure' => $navalPressure,
            'nearby' => $nearby,
            'nearbyJs' => $nearbyJs,
            'arrivals' => $arrivals,
            'vesselsLive' => $vesselsLive,
            'economies' => $economies,
        ]);
    }

    private function reco(ClaudeService $claude, Port $port, array $best, array $rows, array $sentiment, int $arrivals, array $economies, array $assessment): string
    {
        $resume = collect($rows)->map(fn ($r) =>
            "{$r['label_jour']}: saturation {$r['saturation_pct']}%, meteo {$r['meteo_score']}/100, sentiment eco {$r['news_sentiment']}, risque {$r['risk']}/100"
        )->implode("\n");

        $facteurs = collect($assessment['factors'])->map(fn ($f) =>
            "{$f['label']}: {$f['raw']}/100 (poids {$f['weight']}, {$f['note']})"
        )->implode("; ");

        $system = "Tu es SmartPort, copilote logistique IA pour l'import-export au Maroc. "
            . "Tu conseilles importateurs/exportateurs sur le meilleur creneau pour faire arriver/partir des conteneurs, "
            . "en evitant saturation portuaire, mauvaise meteo marine et contexte economique defavorable. "
            . "Reponds en francais, ton professionnel et concret, 3-4 phrases max. Pas de markdown.";

        $prompt = "Port: {$port->nom} ({$port->ville}).\n"
            . "Risque temps reel aujourd'hui: {$assessment['risk']}/100, facteur dominant: {$assessment['driver']}.\n"
            . "Detail des facteurs: {$facteurs}.\n"
            . "Navires actuellement attendus a ce port (AIS): {$arrivals}.\n"
            . "Contexte economique actuel (actualite reelle): {$sentiment['resume']} (sentiment {$sentiment['score']}/100).\n"
            . "Previsions 7 jours:\n{$resume}\n\n"
            . "Le meilleur creneau identifie est {$best['label_jour']} (risque {$best['risk']}/100). "
            . "Economie estimee sur ce creneau: {$economies['mad']} MAD et {$economies['heures']}h d'attente evitees.\n"
            . "Donne une recommandation: cite le facteur dominant du risque actuel, indique quel jour viser et pourquoi, "
            . "et chiffre le benefice (MAD + heures economisees). 3-4 phrases.";

        $mock = "Recommandation SmartPort : visez une arrivée le {$best['label_jour']} sur {$port->nom}. "
            . "Le risque de saturation y est le plus bas ({$best['risk']}/100), avec une fenêtre météo favorable. "
            . "Contexte économique : {$sentiment['resume']} "
            . "En ciblant ce créneau, vous économisez environ {$economies['mad']} MAD de surestaries et {$economies['heures']}h d'attente au mouillage.";

        return $claude->ask($system, $prompt, $mock);
    }
}
