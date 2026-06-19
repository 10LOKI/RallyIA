<?php

namespace App\Services;

/**
 * Construit un trajet maritime PLAUSIBLE (suit les mers, evite les terres)
 * via les points de passage reels des grandes routes commerciales :
 * Malacca, ocean Indien, Bab-el-Mandeb, mer Rouge, canal de Suez,
 * Mediterranee, detroit de Gibraltar, façade atlantique marocaine.
 *
 * Retourne une liste ordonnee de [lat, lng] (origine -> port d'arrivee).
 */
class SeaRouteService
{
    private array $p = [
        'malacca'     => [1.43, 102.9],
        'indian'      => [6.0, 68.0],
        'babMandeb'   => [12.6, 43.4],
        'redSea'      => [20.0, 38.0],
        'suez'        => [29.9, 32.55],
        'portSaid'    => [31.3, 32.3],
        'medEast'     => [34.2, 22.0],
        'malta'       => [35.9, 14.5],
        'medWest'     => [36.7, -1.5],
        'gibMed'      => [35.95, -5.1],   // approche Tanger Med (côté Méditerranée)
        'gib'         => [35.95, -5.75],  // détroit de Gibraltar
        'aegean'      => [36.5, 24.5],
        'capeVincent' => [36.9, -9.4],
        'biscay'      => [44.5, -8.5],
        'channel'     => [48.8, -6.0],
    ];

    public function path(float $oLat, float $oLng, float $destLat, float $destLng): array
    {
        // Origines tres proches : ligne directe (deja en mer)
        if ($this->haversine($oLat, $oLng, $destLat, $destLng) < 450) {
            return [[$oLat, $oLng], [$destLat, $destLng]];
        }

        $wp = [[$oLat, $oLng]];
        $tangerMed = ($destLat > 35.4 && $destLng > -6.0 && $destLng < -4.5);

        if ($oLng > 35 && $oLat < 35) {
            // Est de Suez : Asie / océan Indien / Golfe
            if ($oLng > 95) {
                $wp[] = $this->p['malacca'];
            }
            $wp[] = $this->p['indian'];
            $wp[] = $this->p['babMandeb'];
            $wp[] = $this->p['redSea'];
            $wp[] = $this->p['suez'];
            $wp[] = $this->p['portSaid'];
            $wp[] = $this->p['medEast'];
            $wp[] = $this->p['malta'];
            $this->approach($wp, $tangerMed, $destLat, $destLng);
        } elseif ($oLat >= 30 && $oLat <= 47 && $oLng >= -6 && $oLng <= 36) {
            // Méditerranée
            if ($oLng > 18) {
                $wp[] = $this->p['aegean'];
                $wp[] = $this->p['malta'];
            } elseif ($oLng > 8) {
                $wp[] = $this->p['malta'];
            }
            $this->approach($wp, $tangerMed, $destLat, $destLng);
        } elseif ($oLat > 43 || $oLng < -8) {
            // Atlantique nord (Europe du Nord)
            $wp[] = $this->p['channel'];
            $wp[] = $this->p['biscay'];
            $wp[] = $this->p['capeVincent'];
            if ($tangerMed) {
                $wp[] = $this->p['gib'];
            } else {
                $wp[] = [$destLat + 1.2, $destLng - 1.0];
            }
        } else {
            // générique : courbe vers le large
            $wp[] = [($oLat + $destLat) / 2, ($oLng + $destLng) / 2 - 3];
        }

        $wp[] = [$destLat, $destLng];
        return $wp;
    }

    /** Connexion finale Méditerranée -> port (Tanger Med direct, ou sortie Gibraltar pour l'Atlantique). */
    private function approach(array &$wp, bool $tangerMed, float $destLat, float $destLng): void
    {
        $wp[] = $this->p['medWest'];
        if ($tangerMed) {
            $wp[] = $this->p['gibMed'];
        } else {
            $wp[] = $this->p['gib'];
            $wp[] = [$destLat + 1.2, $destLng - 1.0]; // au large du port atlantique
        }
    }

    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
