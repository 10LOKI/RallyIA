<?php

namespace App\Services;

use App\Models\Port;
use App\Models\Vessel;
use Illuminate\Support\Collection;

class VesselService
{
    /** Mots-cles AIS "Destination" par port (saisie libre par les capitaines). */
    private array $aliases = [
        'Tanger Med'         => ['TANGER', 'TANGIER', 'MAPTM', 'TANGER MED', 'TANGERMED', 'MA TNG'],
        'Port de Casablanca' => ['CASABLANCA', 'CASA', 'MACAS', 'MA CAS'],
        'Port de Agadir'     => ['AGADIR', 'MAAGA', 'MA AGA'],
    ];

    /** Navires vus recemment (par defaut 6h). */
    public function recent(int $hours = 6): Collection
    {
        return Vessel::whereNotNull('lat')
            ->where('seen_at', '>=', now()->subHours($hours))
            ->orderByDesc('seen_at')
            ->get();
    }

    public function count(int $hours = 6): int
    {
        return $this->recent($hours)->count();
    }

    /** Navires dans un rayon (km) autour d'un port. */
    public function nearPort(Port $port, float $km = 60, int $hours = 6): Collection
    {
        return $this->recent($hours)->filter(
            fn (Vessel $v) => $this->haversine($port->lat, $port->lng, $v->lat, $v->lng) <= $km
        )->values();
    }

    /** Navires dont la destination AIS correspond au port. */
    public function expectedArrivals(Port $port, int $hours = 12): Collection
    {
        $keys = $this->aliases[$port->nom] ?? [];
        if (empty($keys)) {
            return collect();
        }

        return $this->recent($hours)
            ->filter(function (Vessel $v) use ($keys) {
                $dest = strtoupper($v->destination ?? '');
                if ($dest === '') {
                    return false;
                }
                foreach ($keys as $k) {
                    if (str_contains($dest, $k)) {
                        return true;
                    }
                }
                return false;
            })
            ->values();
    }

    public function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
