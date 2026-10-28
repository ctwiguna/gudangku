<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman depan hanya mengarahkan ke dashboard.
     * Tamu yang belum login akan dilanjutkan ke halaman login oleh middleware auth.
     */
    public function test_root_url_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }
}
