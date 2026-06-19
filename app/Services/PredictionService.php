<?php

namespace App\Services;

use App\Models\Port;
use Carbon\Carbon;

/**
 * Prevision d'arrivee (ETA) d'un conteneur maritime vers un port marocain.
 *
 * Logique metier (Asie/Europe -> Afrique du Nord) :
 *  - fourchette de transit selon le port d'origine (liaisons reelles),
 *  - direct vs transbordement (hubs : Singapour, Colombo, Algeciras),
 *  - FCL (conteneur complet) vs LCL (groupage : +consolidation/deconsolidation),
 *  - facteurs de retard explicables : meteo, congestion portuaire, douane,
 *  - 3 scenarios (optimiste / realiste / pessimiste) + indice de confiance,
 *  - n° de connaissement (B/L) pour le suivi (Searates / compagnie).
 */
class PredictionService
{
    /**
     * Ports d'origine. transit = [min, max] jours vers Tanger Med (liaison maritime).
     * hub = point de transbordement habituel ; coords pour la carte.
     */
    public function origins(): array
    {
        return [
            'Shanghai (Chine)'        => ['min' => 30, 'max' => 42, 'hub' => 'Singapour',  'lat' => 31.2304, 'lng' => 121.4737],
            'Ningbo (Chine)'          => ['min' => 30, 'max' => 42, 'hub' => 'Singapour',  'lat' => 29.8683, 'lng' => 121.5440],
            'Shenzhen (Chine)'        => ['min' => 28, 'max' => 40, 'hub' => 'Colombo',    'lat' => 22.5431, 'lng' => 114.0579],
            'Singapour'               => ['min' => 20, 'max' => 28, 'hub' => null,         'lat' => 1.3521,  'lng' => 103.8198],
            'Jebel Ali (EAU)'         => ['min' => 12, 'max' => 18, 'hub' => null,         'lat' => 25.0110, 'lng' => 55.0610],
            'Mumbai (Inde)'           => ['min' => 16, 'max' => 24, 'hub' => 'Colombo',    'lat' => 18.9499, 'lng' => 72.8347],
            'Rotterdam (Pays-Bas)'    => ['min' => 6,  'max' => 10, 'hub' => null,         'lat' => 51.9244, 'lng' => 4.4777],
            'Valence (Espagne)'       => ['min' => 2,  'max' => 4,  'hub' => null,         'lat' => 39.4699, 'lng' => -0.3763],
            'Istanbul (Turquie)'      => ['min' => 7,  'max' => 11, 'hub' => 'Le Pirée',   'lat' => 41.0082, 'lng' => 28.9784],
            'Algeciras (Espagne)'     => ['min' => 1,  'max' => 2,  'hub' => null,         'lat' => 36.1408, 'lng' => -5.4562],
        ];
    }

    public function carriers(): array
    {
        // prefixe B/L + fiabilite (impacte la confiance)
        return [
            'CMA CGM'    => ['prefix' => 'CMAU', 'fiabilite' => 0.92],
            'Maersk'     => ['prefix' => 'MAEU', 'fiabilite' => 0.94],
            'MSC'        => ['prefix' => 'MSCU', 'fiabilite' => 0.90],
            'Hapag-Lloyd'=> ['prefix' => 'HLCU', 'fiabilite' => 0.91],
        ];
    }

    private function destOffset(string $portNom): int
    {
        return match ($portNom) {
            'Port de Casablanca' => 1,
            'Port de Agadir'     => 2,
            default              => 0, // Tanger Med
        };
    }

    /**
     * @param string $type    'FCL' | 'LCL'
     * @param string $routing 'Direct' | 'Transbordement'
     * @param array  $rows    lignes 7j du port destination (date Carbon, risk, meteo_score)
     */
    public function predict(
        string $originKey,
        Port $dest,
        Carbon $depart,
        string $type,
        string $routing,
        string $carrierKey,
        array $rows,
    ): array {
        $origins = $this->origins();
        $origin = $origins[$originKey] ?? array_values($origins)[0];
        $carriers = $this->carriers();
        $carrier = $carriers[$carrierKey] ?? array_values($carriers)[0];

        $offset = $this->destOffset($dest->nom);
        $transship = ($routing === 'Transbordement');
        $lcl = ($type === 'LCL');

        // Fourchette de transit maritime
        $tMin = $origin['min'] + $offset + ($transship ? 3 : 0) + ($lcl ? 2 : 0);
        $tMax = $origin['max'] + $offset + ($transship ? 6 : 0) + ($lcl ? 4 : 0);
        $tBase = (int) round(($tMin + $tMax) / 2);

        // ETA "porte" de reference (avant retards) pour lire meteo/congestion a l'arrivee
        $etaRef = $depart->copy()->addDays($tBase);
        $riskArr = $this->valueAt($etaRef, $rows, 'risk', 45);
        $meteoArr = $this->valueAt($etaRef, $rows, 'meteo_score', 70);

        // Facteurs de retard (jours) — explicables
        $retardMeteo = (int) round((100 - $meteoArr) / 100 * 2.5);
        $retardCongestion = (int) round($riskArr / 100 * 3) + ($transship ? 1 : 0);
        $retardDouane = ($lcl ? 2 : 1) + ($riskArr >= 60 ? 1 : 0);
        $retardTotal = $retardMeteo + $retardCongestion + $retardDouane;

        // 3 scenarios
        $optimiste = $depart->copy()->addDays($tMin);
        $realiste  = $depart->copy()->addDays($tBase + $retardTotal);
        $pessimiste = $depart->copy()->addDays($tMax + $retardTotal + 1);
        if ($realiste->lt($optimiste)) {
            $realiste = $optimiste->copy();
        }
        if ($pessimiste->lt($realiste)) {
            $pessimiste = $realiste->copy()->addDay();
        }

        // Confiance : variance + risque + transbordement + groupage + fiabilite armateur
        $confiance = 95;
        $confiance -= ($tMax - $tMin) * 1.5;     // incertitude de la liaison
        $confiance -= $riskArr * 0.15;           // congestion a l'arrivee
        $confiance -= $transship ? 8 : 0;        // escale = alea
        $confiance -= $lcl ? 6 : 0;              // groupage = manutention sup.
        $confiance *= $carrier['fiabilite'];
        $confiance = (int) round(min(95, max(50, $confiance)));

        return [
            'origin' => $originKey,
            'origin_coords' => ['lat' => $origin['lat'], 'lng' => $origin['lng']],
            'hub' => $transship ? ($origin['hub'] ?? 'Algeciras') : null,
            'type' => $type,
            'routing' => $routing,
            'carrier' => $carrierKey,
            'transit_min' => $tMin,
            'transit_max' => $tMax,
            'transit_base' => $tBase,
            'depart' => $depart,
            'eta_optimiste' => $optimiste,
            'eta_realiste' => $realiste,
            'eta_pessimiste' => $pessimiste,
            'risk_arrivee' => $riskArr,
            'confiance' => $confiance,
            'retard_total' => $retardTotal,
            'retards' => [
                ['label' => 'Météo marine', 'jours' => $retardMeteo, 'note' => $meteoArr < 50 ? 'Mer agitée probable' : 'Conditions correctes'],
                ['label' => 'Congestion portuaire', 'jours' => $retardCongestion, 'note' => $transship ? 'Inclut escale de transbordement' : 'Port d\'arrivée'],
                ['label' => 'Douane & formalités', 'jours' => $retardDouane, 'note' => $lcl ? 'Groupage : dédouanement plus long' : 'Conteneur complet (FCL)'],
            ],
            'bl_number' => $this->blNumber($carrier['prefix'], $originKey, $dest->id, $depart),
        ];
    }

    /** Lit une valeur de la prevision a une date (sinon moyenne / defaut). */
    private function valueAt(Carbon $date, array $rows, string $key, int $default): int
    {
        foreach ($rows as $r) {
            if (isset($r['date']) && $r['date']->isSameDay($date)) {
                return (int) ($r[$key] ?? $default);
            }
        }
        $vals = array_filter(array_column($rows, $key), fn ($v) => $v !== null);
        return count($vals) ? (int) round(array_sum($vals) / count($vals)) : $default;
    }

    /** N° de connaissement (B/L) deterministe pour le suivi. */
    private function blNumber(string $prefix, string $origin, int $destId, Carbon $depart): string
    {
        $seed = crc32($origin . $destId . $depart->toDateString());
        return $prefix . str_pad((string) ($seed % 9000000 + 1000000), 7, '0', STR_PAD_LEFT);
    }
}
