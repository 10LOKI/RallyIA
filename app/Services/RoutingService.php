<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingService
{
    /**
     * Itineraire OSRM entre 2 points [lng,lat].
     * Retourne geometry GeoJSON, distance (km), duree (min).
     */
    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        $base = config('services.osrm.url');
        $coords = "{$fromLng},{$fromLat};{$toLng},{$toLat}";

        try {
            $res = Http::timeout(20)->get("{$base}/route/v1/driving/{$coords}", [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
            ]);

            if ($res->failed() || $res->json('code') !== 'Ok') {
                Log::warning('OSRM echec', ['body' => $res->body()]);
                return $this->fallback($fromLat, $fromLng, $toLat, $toLng);
            }

            $r = $res->json('routes.0');

            return [
                'geometry' => $r['geometry'],
                'distance_km' => round($r['distance'] / 1000, 1),
                'duree_min' => (int) round($r['duration'] / 60),
            ];
        } catch (\Throwable $e) {
            Log::error('OSRM exception', ['msg' => $e->getMessage()]);
            return $this->fallback($fromLat, $fromLng, $toLat, $toLng);
        }
    }

    /** Ligne droite si OSRM HS — garde la carte vivante en demo. */
    private function fallback(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $dist = $this->haversine($fromLat, $fromLng, $toLat, $toLng);
        return [
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [[$fromLng, $fromLat], [$toLng, $toLat]],
            ],
            'distance_km' => round($dist, 1),
            'duree_min' => (int) round($dist / 60 * 60), // ~60km/h
            'fallback' => true,
        ];
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
