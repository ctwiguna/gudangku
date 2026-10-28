<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /**
     * Props berbeda per role. Manajer tidak menerima angka uang sama sekali:
     * penyaringan terjadi di sini, bukan hanya di komponen React.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isOwner()) {
            return Inertia::render('Dashboard/Manager');
        }

        return Inertia::render('Dashboard/Owner', [
            'summary' => $this->dashboard->ownerSummary(),
            'outlets' => $this->dashboard->outletStatuses(),
            'belowMin' => $this->dashboard->belowMinProducts(),
            'recentReceipts' => $this->dashboard->recentReceipts(),
            'recentExpenses' => $this->dashboard->recentExpenses(),
        ]);
    }
}
