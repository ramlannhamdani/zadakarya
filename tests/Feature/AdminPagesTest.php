<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Customer;
use App\Models\Portfolio;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->create();

        $service = Service::create(['name' => 'Layanan A', 'slug' => 'layanan-a']);
        $portfolio = Portfolio::create(['title' => 'Porto A', 'slug' => 'porto-a']);
        $article = Article::create(['title' => 'Artikel A', 'slug' => 'artikel-a', 'content' => 'Isi', 'user_id' => $admin->id]);
        $customer = Customer::create(['name' => 'Budi']);
        $review = Review::create(['author_name' => 'Ana', 'rating' => 5, 'content' => 'Bagus']);

        $urls = [
            route('admin.services.create'),
            route('admin.services.edit', $service),
            route('admin.portfolio.create'),
            route('admin.portfolio.edit', $portfolio),
            route('admin.articles.create'),
            route('admin.articles.edit', $article),
            route('admin.customers.create'),
            route('admin.customers.edit', $customer),
            route('admin.reviews.create'),
            route('admin.reviews.edit', $review),
            route('admin.settings.edit'),
            route('admin.gallery.index'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_dashboard_shows_revenue_from_payments(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::create(['name' => 'Budi']);
        $order = \App\Models\Order::create([
            'order_number' => 'ZDK-0001-010126',
            'customer_id' => $customer->id,
            'name' => 'Uji Revenue',
            'grand_total' => 10000000,
        ]);
        $order->payments()->create(['amount' => 4000000, 'payment_date' => now()->toDateString(), 'method' => 'transfer']);
        $order->refreshPaymentStatus();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Pendapatan')
            ->assertSee('Rp 4.000.000')   // terbayar
            ->assertSee('Rp 6.000.000');  // piutang
    }
}
