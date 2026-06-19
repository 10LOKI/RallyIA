<?php

namespace App\Services;

use App\Models\Port;
use Carbon\Carbon;

/**
 * Predit la date d'arrivee d'un conteneur selon le port d'origine,
 * le port marocain de destination et le creneau de depart choisi.
 * Ajuste l'ETA avec le risque de saturation prevu a l'arrivee.
 */
class PredictionService
{
    /** Ports d'origine + duree de transit maritime (jours) vers Tanger Med + coords. */
    public function origins(): array
    {
        return [
            'Shanghai (Chine)'      => ['transit' => 28, 'lat' => 31.2304, 'lng' => 121.4737],
            'Rotterdam (Pays-Bas)'  => ['transit' => 6,  'lat' => 51.9244, 'lng' => 4.4777],
            'Valence (Espagne)'     => ['transit' => 2,  'lat' => 39.4699, 'lng' => -0.3763],
            'Marseille (France)'    => ['transit' => 3,  'lat' => 43.2965, 'lng' => 5.3698],
            'Istanbul (Turquie)'    => ['transit' => 7,  'lat' => 41.0082, 'lng' => 28.9784],
            'Algeciras (Espagne)'   => ['transit' => 1,  'lat' => 36.1408, 'lng' => -5.4562],
        ];
    }

    /** Jours additionnels selon le port marocain d'arrivee. */
    private function destOffset(string $portNom): int
    {
        return match ($portNom) {
            'Port de Casablanca' => 1,
            'Port de Agadir'     => 2,
            default              => 0, // Tanger Med
        };
    }

    /**
     * @param array $forecastRows lignes 7j du port destination (avec 'date' Carbon + 'risk')
     */
    public function predict(string $originKey, Port $dest, Carbon $depart, array $forecastRows): array
    {
        $origins = $this->origins();
        $origin = $origins[$originKey] ?? array_values($origins)[0];

        $transit = $origin['transit'] + $this->destOffset($dest->nom);
        $etaBase = $depart->copy()->addDays($transit);

        // Risque de saturation prevu a l'arrivee (si dans la fenetre 7j connue, sinon moyenne)
        $risk = $this->riskAt($etaBase, $forecastRows);
        $bufferJours = (int) round($risk / 100 * 2.5);

        $etaMin = $etaBase->copy();
        $etaMax = $etaBase->copy()->addDays($bufferJours);
        $confiance = (int) round(max(55, 100 - $risk / 2));

        return [
            'origin' => $originKey,
            'origin_coords' => ['lat' => $origin['lat'], 'lng' => $origin['lng']],
            'transit_jours' => $transit,
            'depart' => $depart,
            'eta_base' => $etaBase,
            'eta_min' => $etaMin,
            'eta_max' => $etaMax,
            'buffer_jours' => $bufferJours,
            'risk_arrivee' => $risk,
            'confiance' => $confiance,
        ];
    }

    private function riskAt(Carbon $eta, array $rows): int
    {
        foreach ($rows as $r) {
            if (isset($r['date']) && $r['date']->isSameDay($eta)) {
                return (int) $r['risk'];
            }
        }
        // hors fenetre connue : moyenne des risques disponibles
        $risques = array_column($rows, 'risk');
        return count($risques) ? (int) round(array_sum($risques) / count($risques)) : 45;
    }
}
