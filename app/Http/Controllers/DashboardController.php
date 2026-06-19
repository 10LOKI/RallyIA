<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Shipment;
use App\Services\PortSaturationService;

class DashboardController extends Controller
{
    public function index(PortSaturationService $sat)
    {
        $ports = Port::with('conditions')->get();

        $stats = $ports->map(function (Port $p) use ($sat) {
            $today = $p->conditions->sortBy('date')->first();
            $risk = $today ? $sat->risk($today) : 0;
            return [
                'port' => $p,
                'risk' => $risk,
                'level' => $sat->level($risk),
                'saturation' => $today?->saturation_pct ?? 0,
            ];
        });

        $shipments = Shipment::with('port')->latest()->get();

        return view('dashboard', compact('stats', 'shipments'));
    }
}
