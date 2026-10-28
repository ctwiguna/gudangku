<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Halaman sementara sampai modul autentikasi selesai.
     * Route memakai controller, bukan closure, supaya `route:cache`
     * tetap bisa dijalankan di produksi.
     */
    public function index(): Response
    {
        return Inertia::render('Home');
    }
}
