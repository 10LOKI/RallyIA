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
