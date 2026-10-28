<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /** Dipakai agar setiap aksi bisa memanggil $this->authorize(). */
    use AuthorizesRequests;
}
