<?php

namespace App\Services;

/**
 * Estimation du coût d'un transport conteneur (import vers le Maroc).
 * Postes transparents, calibrés sur les ordres de grandeur du marché.
 * Tout est annoncé comme "estimation".
 */
class CostService
{
    private const MAD_PER_USD = 10.0;
    private const MAD_PER_EUR = 11.0;

    /**
     * @param string $type        FCL | LCL
     * @param int    $transitBase  jours de transit (proxy de distance)
     * @param int    $riskArrivee  0..100 (risque saturation -> surestaries potentielles)
     * @param float  $landKm       distance terrestre port -> ville (0 si inconnue)
     */
    public function estimate(string $type, int $transitBase, int $riskArrivee, float $landKm = 0): array
    {
        $lcl = ($type === 'LCL');

        // Fret maritime : base + coût/jour de transit
        $fret = 6000 + $transitBase * 600;
        if ($lcl) {
            $fret = (int) round($fret * 0.45) + 2500; // groupage : moins cher mais manutention/u sup.
        }

        $thc = 1800 + ($lcl ? 1000 : 1800);            // manutention terminal (origine + destination)
        $douane = $lcl ? 3200 : 2500;                  // dédouanement
        $assurance = 1500;                             // assurance cargo (forfait estimé)
        $documentaire = 900;                           // B/L, ISPS, frais documentaires
        $terrestre = $landKm > 0 ? (int) round(max(800, $landKm * 22)) : 0; // acheminement camion
        $surestaries = (int) round($riskArrivee / 100 * 2 * 1500);          // attente probable à l'arrivée

        $postes = [
            ['label' => 'Fret maritime', 'mad' => (int) $fret, 'note' => $lcl ? 'Groupage (LCL)' : 'Conteneur complet (FCL)'],
            ['label' => 'Manutention terminal (THC)', 'mad' => $thc, 'note' => 'Origine + destination'],
            ['label' => 'Dédouanement', 'mad' => $douane, 'note' => 'Droits & formalités'],
            ['label' => 'Assurance cargo', 'mad' => $assurance, 'note' => 'Forfait estimé'],
            ['label' => 'Frais documentaires', 'mad' => $documentaire, 'note' => 'B/L, ISPS'],
        ];
        if ($terrestre > 0) {
            $postes[] = ['label' => 'Acheminement terrestre', 'mad' => $terrestre, 'note' => round($landKm) . ' km camion'];
        }
        if ($surestaries > 0) {
            $postes[] = ['label' => 'Surestaries probables', 'mad' => $surestaries, 'note' => 'Selon risque saturation', 'risk' => true];
        }

        $total = array_sum(array_column($postes, 'mad'));

        return [
            'postes' => $postes,
            'total_mad' => $total,
            'total_eur' => (int) round($total / self::MAD_PER_EUR),
            'total_usd' => (int) round($total / self::MAD_PER_USD),
            'surestaries' => $surestaries,
        ];
    }
}
