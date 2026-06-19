<?php

namespace App\Services;

use App\Models\Port;
use App\Models\PortCondition;

class PortSaturationService
{
    /**
     * Score de risque 0..100 (plus haut = plus risque) pour une condition donnee.
     * Pondere: saturation 50%, meteo 30% (inversee), sentiment eco 20%.
     */
    public function risk(PortCondition $c): int
    {
        return $this->riskValues($c->saturation_pct, $c->meteo_score, $c->news_sentiment);
    }

    /** Score risque 0..100 a partir des valeurs brutes (reutilisable avec sentiment news reel). */
    public function riskValues(int $saturation, int $meteo, int $sentiment): int
    {
        $satRisk = $saturation;                        // 0..100
        $meteoRisk = 100 - $meteo;                     // calme=0 risque, tempete=100
        $newsRisk = (100 - ($sentiment + 100) / 2);    // sentiment+ => risque-

        $score = ($satRisk * 0.5) + ($meteoRisk * 0.3) + ($newsRisk * 0.2);

        return (int) round(min(100, max(0, $score)));
    }

    public function level(int $risk): array
    {
        return match (true) {
            $risk < 35 => ['label' => 'Faible', 'color' => 'emerald', 'hex' => '#10b981'],
            $risk < 60 => ['label' => 'Modéré', 'color' => 'amber', 'hex' => '#f59e0b'],
            default    => ['label' => 'Élevé', 'color' => 'rose', 'hex' => '#f43f5e'],
        };
    }

    /** Ponderation du modele de risque temps reel (somme = 1). */
    private const WEIGHTS = [
        'saturation' => 0.35,
        'navires'    => 0.25,
        'meteo'      => 0.25,
        'economie'   => 0.15,
    ];

    /**
     * Pression navale 0..100 a partir des signaux AIS:
     * navires attendus (poids fort) + navires a proximite (poids faible).
     */
    public function navalPressure(int $arrivals, int $nearby): int
    {
        return (int) round(min(100, $arrivals * 14 + $nearby * 2.5));
    }

    /**
     * Evaluation temps reel EXPLICABLE du risque de saturation.
     * Retourne le score + la contribution detaillee de chaque facteur (pour le jury).
     */
    public function assess(int $saturation, int $meteo, int $sentiment, int $navalPressure): array
    {
        $raw = [
            'saturation' => $this->clamp($saturation),
            'navires'    => $this->clamp($navalPressure),
            'meteo'      => $this->clamp(100 - $meteo),
            'economie'   => $this->clamp(100 - ($sentiment + 100) / 2),
        ];

        $factors = [];
        $risk = 0.0;
        foreach (self::WEIGHTS as $key => $weight) {
            $contribution = $raw[$key] * $weight;
            $risk += $contribution;
            $factors[] = [
                'key' => $key,
                'label' => $this->factorLabel($key),
                'raw' => (int) round($raw[$key]),
                'weight' => $weight,
                'contribution' => round($contribution, 1),
                'note' => $this->factorNote($key, $raw[$key]),
            ];
        }

        // facteur dominant = plus grosse contribution
        usort($factors, fn ($a, $b) => $b['contribution'] <=> $a['contribution']);
        $risk = (int) round(min(100, max(0, $risk)));

        return [
            'risk' => $risk,
            'level' => $this->level($risk),
            'factors' => $factors,
            'driver' => $factors[0]['label'],
        ];
    }

    private function clamp(float $v): float
    {
        return min(100, max(0, $v));
    }

    private function factorLabel(string $key): string
    {
        return match ($key) {
            'saturation' => 'Saturation portuaire',
            'navires'    => 'Pression navale (AIS)',
            'meteo'      => 'Météo marine',
            'economie'   => 'Contexte économique',
        };
    }

    private function factorNote(string $key, float $raw): string
    {
        $niveau = $raw >= 60 ? 'élevé' : ($raw >= 35 ? 'modéré' : 'faible');
        return match ($key) {
            'saturation' => "Taux de remplissage {$niveau}",
            'navires'    => "Afflux de navires {$niveau}",
            'meteo'      => $raw >= 50 ? 'Conditions dégradées' : 'Fenêtre favorable',
            'economie'   => $raw >= 50 ? 'Climat défavorable' : 'Climat porteur',
        };
    }

    /**
     * Retourne les conditions 7j d'un port avec score calcule + meilleur creneau.
     */
    public function forecast(Port $port): array
    {
        $rows = $port->conditions()->orderBy('date')->get()->map(function (PortCondition $c) {
            $risk = $this->risk($c);
            return [
                'date' => $c->date,
                'label_jour' => $c->date->locale('fr')->isoFormat('ddd D MMM'),
                'saturation_pct' => $c->saturation_pct,
                'meteo_score' => $c->meteo_score,
                'news_sentiment' => $c->news_sentiment,
                'risk' => $risk,
                'level' => $this->level($risk),
            ];
        })->all();

        $best = collect($rows)->sortBy('risk')->first();

        return ['rows' => $rows, 'best' => $best];
    }
}
