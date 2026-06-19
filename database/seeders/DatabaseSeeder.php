<?php

namespace Database\Seeders;

use App\Models\Port;
use App\Models\PortCondition;
use App\Models\Shipment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Port::query()->delete();

        $ports = [
            [
                'nom' => 'Tanger Med',
                'ville' => 'Tanger',
                'lat' => 35.8838,
                'lng' => -5.4980,
                'capacite_max' => 9000,
            ],
            [
                'nom' => 'Port de Casablanca',
                'ville' => 'Casablanca',
                'lat' => 33.6056,
                'lng' => -7.6056,
                'capacite_max' => 4000,
            ],
            [
                'nom' => 'Port de Agadir',
                'ville' => 'Agadir',
                'lat' => 30.4167,
                'lng' => -9.6333,
                'capacite_max' => 1500,
            ],
        ];

        // Profils de saturation simules sur 7 jours (mock realiste)
        $profils = [
            'Tanger Med'        => [62, 78, 45, 30, 55, 88, 40],
            'Port de Casablanca'=> [50, 65, 72, 81, 60, 48, 35],
            'Port de Agadir'    => [30, 42, 38, 55, 60, 25, 33],
        ];
        $meteo = [
            'Tanger Med'        => [70, 55, 85, 92, 60, 35, 88],
            'Port de Casablanca'=> [80, 75, 60, 50, 70, 82, 90],
            'Port de Agadir'    => [88, 80, 75, 65, 70, 90, 85],
        ];
        $sentiment = [
            'Tanger Med'        => [20, -15, 35, 40, 10, -30, 25],
            'Port de Casablanca'=> [15, 5, -10, -25, 20, 30, 40],
            'Port de Agadir'    => [30, 25, 10, -5, 15, 35, 28],
        ];

        foreach ($ports as $data) {
            $port = Port::create($data);

            for ($i = 0; $i < 7; $i++) {
                PortCondition::create([
                    'port_id' => $port->id,
                    'date' => now()->addDays($i)->toDateString(),
                    'saturation_pct' => $profils[$port->nom][$i],
                    'meteo_score' => $meteo[$port->nom][$i],
                    'news_sentiment' => $sentiment[$port->nom][$i],
                ]);
            }
        }

        // Scenario demo
        $tanger = Port::where('nom', 'Tanger Med')->first();
        Shipment::create([
            'port_id' => $tanger->id,
            'reference' => 'LM-2026-0042',
            'marchandise' => 'Composants electroniques',
            'origine' => 'Shanghai, Chine',
            'destination_ville' => 'Casablanca',
            'dest_lat' => 33.5731,
            'dest_lng' => -7.5898,
            'statut' => 'en_mer',
        ]);

        $casa = Port::where('nom', 'Port de Casablanca')->first();
        Shipment::create([
            'port_id' => $casa->id,
            'reference' => 'LM-2026-0043',
            'marchandise' => 'Textile pret-a-porter',
            'origine' => 'Istanbul, Turquie',
            'destination_ville' => 'Rabat',
            'dest_lat' => 34.0209,
            'dest_lng' => -6.8416,
            'statut' => 'au_port',
        ]);
    }
}
