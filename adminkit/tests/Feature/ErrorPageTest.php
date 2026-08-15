<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_404_memakai_halaman_error_inertia(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertStatus(404)
            ->assertSee('Error')
            ->assertSee('"status":404', false);
    }

    public function test_403_memakai_halaman_error_inertia(): void
    {
        Route::middleware('web')->get('/__uji-403', fn () => abort(403));

        $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->firstOrFail();

        $this->actingAs($user)
            ->get('/__uji-403')
            ->assertStatus(403)
            ->assertSee('"component":"Error"', false)
            ->assertSee('"status":403', false);
    }

    public function test_permintaan_json_tetap_menerima_json(): void
    {
        $this->getJson('/halaman-yang-tidak-ada')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/json');
    }
}
