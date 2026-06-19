<?php

namespace App\Console\Commands;

use App\Models\Vessel;
use Illuminate\Console\Command;
use WebSocket\Client;

/**
 * Collecte les navires AIS autour du Maroc via aisstream.io (WebSocket)
 * pendant N secondes, puis upsert en base. A lancer avant la demo / via scheduler.
 */
class VesselsPoll extends Command
{
    protected $signature = 'vessels:poll {--seconds=25 : Duree de collecte}';
    protected $description = 'Collecte les positions AIS navires (aisstream.io) autour du Maroc';

    public function handle(): int
    {
        $key = config('services.aisstream.key');
        if (empty($key)) {
            $this->error('AISSTREAM_KEY manquante dans .env');
            return self::FAILURE;
        }

        $seconds = (int) $this->option('seconds');
        $url = config('services.aisstream.url');

        // Bounding box cote marocaine (Atlantique + Mediterranee + detroit Gibraltar)
        $bbox = [[[29.0, -12.0], [37.5, -4.0]]];

        $sub = json_encode([
            'APIKey' => $key,
            'BoundingBoxes' => $bbox,
            'FilterMessageTypes' => ['PositionReport', 'ShipStaticData'],
        ]);

        $this->info("Connexion aisstream… collecte {$seconds}s");

        try {
            $client = new Client($url, ['timeout' => 15]);
            $client->send($sub);
        } catch (\Throwable $e) {
            $this->error('Connexion WS echec: ' . $e->getMessage());
            return self::FAILURE;
        }

        $deadline = microtime(true) + $seconds;
        $count = 0;
        $seen = [];

        while (microtime(true) < $deadline) {
            try {
                $raw = $client->receive();
            } catch (\Throwable $e) {
                // timeout de receive: on continue jusqu'au deadline
                if (str_contains($e->getMessage(), 'timeout')) {
                    continue;
                }
                break;
            }

            if (!$raw) {
                continue;
            }

            $data = json_decode($raw, true);
            if (!is_array($data) || empty($data['MetaData']['MMSI'])) {
                continue;
            }

            $meta = $data['MetaData'];
            $mmsi = (int) $meta['MMSI'];
            $type = $data['MessageType'] ?? '';

            $payload = [
                'name' => trim($meta['ShipName'] ?? '') ?: null,
                'seen_at' => now(),
            ];

            if ($type === 'PositionReport') {
                $pr = $data['Message']['PositionReport'] ?? [];
                $payload['lat'] = $meta['latitude'] ?? ($pr['Latitude'] ?? null);
                $payload['lng'] = $meta['longitude'] ?? ($pr['Longitude'] ?? null);
                $payload['sog'] = $pr['Sog'] ?? null;
                $payload['cog'] = $pr['Cog'] ?? null;
                $payload['nav_status'] = isset($pr['NavigationalStatus'])
                    ? (string) $pr['NavigationalStatus'] : null;
            } elseif ($type === 'ShipStaticData') {
                $sd = $data['Message']['ShipStaticData'] ?? [];
                $dest = trim($sd['Destination'] ?? '');
                $payload['destination'] = $dest !== '' ? $dest : null;
                if (!empty($sd['Eta']) && is_array($sd['Eta'])) {
                    $e = $sd['Eta'];
                    $payload['eta'] = sprintf('%02d-%02d %02d:%02d',
                        $e['Month'] ?? 0, $e['Day'] ?? 0, $e['Hour'] ?? 0, $e['Minute'] ?? 0);
                }
                if (!empty($sd['Type'])) {
                    $payload['ship_type'] = (string) $sd['Type'];
                }
            } else {
                continue;
            }

            $payload = array_filter($payload, fn ($v) => $v !== null);

            Vessel::updateOrCreate(['mmsi' => $mmsi], $payload);

            if (!isset($seen[$mmsi])) {
                $seen[$mmsi] = true;
                $count++;
            }
        }

        try { $client->close(); } catch (\Throwable) {}

        $total = Vessel::whereNotNull('lat')->where('seen_at', '>=', now()->subHours(6))->count();
        $this->info("Termine. {$count} navires uniques captes. Total recents en base: {$total}");

        return self::SUCCESS;
    }
}
