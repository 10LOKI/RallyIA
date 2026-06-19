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

    /** Navires vus recemment (par defaut 6h). Fallback mock si base vide. */
    public function recent(int $hours = 6): Collection
    {
        $rows = Vessel::whereNotNull('lat')
            ->where('seen_at', '>=', now()->subHours($hours))
            ->orderByDesc('seen_at')
            ->get();

        return $rows->isNotEmpty() ? $rows : $this->mock();
    }

    public function isLive(int $hours = 6): bool
    {
        return Vessel::whereNotNull('lat')->where('seen_at', '>=', now()->subHours($hours))->exists();
    }

    /** Navires simules autour des ports marocains (demo sans flux AIS). */
    private function mock(): Collection
    {
        $defs = [
            // [nom, mmsi, lat, lng, type, destination, eta, sog]
            ['MAERSK TANGIER', 211223334, 35.95, -5.60, 'Cargo', 'TANGER MED', '06-19 14:30', 12.4],
            ['MSC ATLANTIC',   229887766, 35.78, -5.72, 'Cargo', 'TANGER MED', '06-19 18:00', 10.1],
            ['CMA CGM RHONE',  255101202, 35.70, -5.35, 'Container', 'TANGER MED', '06-20 02:15', 14.0],
            ['BANI EXPRESS',   242778899, 35.88, -5.49, 'Tanker', 'TANGER MED', '06-19 09:00', 0.0],
            ['IBN BATTOUTA',   242334455, 33.70, -7.70, 'Container', 'CASABLANCA', '06-19 16:45', 9.6],
            ['ATLAS TRADER',   538009112, 33.55, -7.55, 'Cargo', 'CASABLANCA', '06-20 06:00', 11.2],
            ['SOUSS WAVE',     242556677, 30.50, -9.70, 'Fishing', 'AGADIR', '06-19 12:00', 7.8],
            ['AGADIR STAR',    242667788, 30.35, -9.72, 'Cargo', 'AGADIR', '06-20 10:30', 8.4],
            ['GIBRALTAR PIONEER', 209445566, 35.99, -5.45, 'Container', 'GENOVA', '06-21 08:00', 16.2],
            ['SAHARA BREEZE',  242990011, 34.10, -8.10, 'Tanker', 'CASABLANCA', '06-19 22:00', 13.3],
        ];

        return collect($defs)->map(fn ($d) => new Vessel([
            'mmsi' => $d[1], 'name' => $d[0], 'lat' => $d[2], 'lng' => $d[3],
            'ship_type' => $d[4], 'destination' => $d[5], 'eta' => $d[6], 'sog' => $d[7],
            'nav_status' => $d[7] == 0.0 ? 'À l\'ancre' : 'En route',
            'seen_at' => now()->subMinutes(rand(2, 40)),
        ]));
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
