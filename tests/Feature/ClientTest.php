<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_client_and_it_shows_on_homepage(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'PT Klien Hebat',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 200),
            'website_url' => 'https://klienhebat.example',
            'is_active' => 1,
        ])->assertRedirect(route('admin.clients.index'));

        $client = Client::firstOrFail();
        Storage::disk('public')->assertExists($client->logo_path);

        $this->get('/')->assertOk()->assertSee('Dipercaya Berbagai Instansi')->assertSee('PT Klien Hebat');
    }

    public function test_inactive_clients_are_hidden_and_section_collapses_when_empty(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Dipercaya Berbagai Instansi');

        Client::create(['name' => 'Klien Nonaktif', 'logo_path' => 'clients/x.webp', 'is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('Klien Nonaktif')->assertDontSee('Dipercaya Berbagai Instansi');
    }

    public function test_logo_is_required_when_creating_client(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.clients.store'), ['name' => 'Tanpa Logo'])
            ->assertSessionHasErrors('logo');

        $this->assertSame(0, Client::count());
    }
}
