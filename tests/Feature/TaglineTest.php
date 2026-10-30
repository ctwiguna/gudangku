<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TaglineTest extends TestCase
{
    use RefreshDatabase;

    private const TAGLINE = 'Catat, monitor dan audit stok + Pengeluaran bisnis';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DemoDataSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function inertiaPage(TestResponse $response): array
    {
        $page = $response->viewData('page');

        if (is_string($page)) {
            return json_decode($page, true) ?? [];
        }

        return json_decode(json_encode($page), true) ?? [];
    }

    public function test_tagline_is_configured_in_one_place(): void
    {
        $this->assertSame(self::TAGLINE, config('app.tagline'));
        $this->assertSame('gudangku', config('app.name'));
    }

    /**
     * Tagline tampil di layar login lewat props bersama Inertia.
     */
    public function test_tagline_is_shared_on_login_screen(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Auth/Login', $page['component']);
        $this->assertSame(self::TAGLINE, $page['props']['app']['tagline']);
        $this->assertSame('gudangku', $page['props']['app']['name']);
    }

    public function test_tagline_is_shared_on_register_screen(): void
    {
        $page = $this->inertiaPage($this->get('/register'));

        $this->assertSame('Auth/Register', $page['component']);
        $this->assertSame(self::TAGLINE, $page['props']['app']['tagline']);
    }

    /**
     * Header aplikasi dan catatan kaki memakai props yang sama, jadi
     * mengubah tagline cukup di APP_TAGLINE.
     */
    public function test_tagline_is_shared_on_authenticated_pages(): void
    {
        $owner = User::where('email', 'owner@gudangku.test')->firstOrFail();
        $manager = User::where('email', 'ina@gudangku.test')->firstOrFail();

        foreach ([[$owner, '/dashboard'], [$owner, '/reports'], [$manager, '/dashboard']] as [$user, $path]) {
            $page = $this->inertiaPage($this->actingAs($user)->get($path));

            $this->assertSame(self::TAGLINE, $page['props']['app']['tagline'], "Tagline harus ada di {$path}.");
        }
    }

    /**
     * Layout memakai props dari server, bukan teks yang ditulis ulang.
     */
    public function test_layouts_read_tagline_from_props(): void
    {
        foreach (['resources/js/Layouts/AuthLayout.jsx', 'resources/js/Layouts/AppShell.jsx'] as $file) {
            $source = file_get_contents(base_path($file));

            $this->assertStringContainsString('app?.tagline', $source, "{$file} harus memakai props tagline.");
            $this->assertStringNotContainsString(
                self::TAGLINE,
                $source,
                "{$file} tidak boleh meng-hardcode tagline; ambil dari props.",
            );
        }
    }
}
