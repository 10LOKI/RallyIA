<?php

namespace App\Services;

/**
 * Estimations d'economies LogiMind (chiffres pour le jury).
 * Hypotheses calibrees, transparentes — affichees comme "estimations".
 */
class SavingsService
{
    // Hypotheses metier
    private const DEMURRAGE_MAD_JOUR = 1500;  // surestaries / conteneur / jour
    private const MAX_ATTENTE_JOURS = 3;      // attente max si port sature
    private const CONSO_L_100KM = 30;         // camion poids lourd
    private const DIESEL_MAD_L = 14;          // prix gasoil MA
    private const DETOUR_PCT = 0.12;          // surplus distance sans optimisation
    private const CONGESTION_PCT = 0.30;      // surplus temps en embouteillage

    /** Economies portuaires: meilleur creneau vs moyenne des 7 jours. */
    public function port(array $rows, array $best): array
    {
        $risques = array_column($rows, 'risk');
        $moyenne = count($risques) ? array_sum($risques) / count($risques) : 0;

        $attenteMoyenne = $this->attenteJours($moyenne);
        $attenteBest = $this->attenteJours($best['risk']);
        $joursGagnes = max(0, $attenteMoyenne - $attenteBest);

        return [
            'mad' => (int) round($joursGagnes * self::DEMURRAGE_MAD_JOUR),
            'heures' => (int) round($joursGagnes * 24),
            'jours' => round($joursGagnes, 1),
        ];
    }

    /** Economies trajet: itineraire optimise vs trajet non optimise. */
    public function route(float $distanceKm, int $dureeMin): array
    {
        $litresOptim = $distanceKm * self::CONSO_L_100KM / 100;
        $litresBase = ($distanceKm * (1 + self::DETOUR_PCT)) * self::CONSO_L_100KM / 100;
        $litresGagnes = max(0, $litresBase - $litresOptim);

        $minGagnees = (int) round($dureeMin * self::CONGESTION_PCT);

        return [
            'litres' => (int) round($litresGagnes),
            'mad' => (int) round($litresGagnes * self::DIESEL_MAD_L),
            'minutes' => $minGagnees,
        ];
    }

    private function attenteJours(float $risk): float
    {
        return ($risk / 100) * self::MAX_ATTENTE_JOURS;
    }
}
