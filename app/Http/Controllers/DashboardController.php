<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Props berbeda per role. Manajer tidak menerima angka uang sama sekali.
     * Data agregat lengkap ditambahkan pada tahap dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return $user->isOwner()
            ? Inertia::render('Dashboard/Owner')
            : Inertia::render('Dashboard/Manager');
    }
}
