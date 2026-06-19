<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Shipment;
use App\Services\PortSaturationService;
use App\Services\VesselService;

class DashboardController extends Controller
{
    public function index(PortSaturationService $sat, VesselService $vessels)
    {
        $ports = Port::with('conditions')->get();

        $stats = $ports->map(function (Port $p) use ($sat, $vessels) {
            $today = $p->conditions->sortBy('date')->first();
            $risk = $today ? $sat->risk($today) : 0;
            return [
                'port' => $p,
                'risk' => $risk,
                'level' => $sat->level($risk),
                'saturation' => $today?->saturation_pct ?? 0,
                'vessels' => $vessels->nearPort($p, 70)->count(),
                'arrivals' => $vessels->expectedArrivals($p)->count(),
            ];
        });

        $shipments = Shipment::with('port')->latest()->get();
        $vesselsTotal = $vessels->recent()->count();
        $vesselsLive = $vessels->isLive();

        return view('dashboard', compact('stats', 'shipments', 'vesselsTotal', 'vesselsLive'));
    }
}
