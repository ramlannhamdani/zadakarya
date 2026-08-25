<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Html;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:admin@test.id|127.0.0.1');
        RateLimiter::clear('login-ip:127.0.0.1');
    }

    public function test_login_locks_after_five_failed_attempts_for_same_account(): void
    {
        User::factory()->create(['email' => 'admin@test.id', 'password' => bcrypt('benar-123456')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'admin@test.id', 'password' => 'salah'])
                ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        }

        // Percobaan ke-6 ditolak walaupun password benar — akun terkunci sementara.
        $this->post('/admin/login', ['email' => 'admin@test.id', 'password' => 'benar-123456'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Terlalu banyak percobaan', $errors[0]);
    }

    public function test_successful_login_clears_the_account_limiter(): void
    {
        User::factory()->create(['email' => 'admin@test.id', 'password' => bcrypt('benar-123456')]);

        $this->post('/admin/login', ['email' => 'admin@test.id', 'password' => 'salah']);
        $this->post('/admin/login', ['email' => 'admin@test.id', 'password' => 'benar-123456'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_admin_can_change_password_with_correct_current_password(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('lama-123456')]);

        $this->actingAs($admin)->patch(route('admin.password.update'), [
            'current_password' => 'lama-123456',
            'password' => 'baru-password-789',
            'password_confirmation' => 'baru-password-789',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('baru-password-789', $admin->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password_and_weak_password(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('lama-123456')]);

        $this->actingAs($admin)->patch(route('admin.password.update'), [
            'current_password' => 'bukan-ini',
            'password' => 'baru-password-789',
            'password_confirmation' => 'baru-password-789',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->patch(route('admin.password.update'), [
            'current_password' => 'lama-123456',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');
    }

    public function test_rich_text_sanitizer_strips_dangerous_markup(): void
    {
        $dirty = '<p onclick="alert(1)">Halo <strong>dunia</strong></p><script>alert("xss")</script>'
            .'<a href="javascript:alert(1)">link</a><iframe src="https://evil.example"></iframe>';

        $clean = Html::clean($dirty);

        $this->assertStringContainsString('<strong>dunia</strong>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
    }

    public function test_article_content_is_sanitized_on_save(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.articles.store'), [
            'title' => 'Uji XSS',
            'content' => '<p>Aman</p><script>alert(1)</script>',
        ])->assertRedirect();

        $article = \App\Models\Article::where('slug', 'uji-xss')->firstOrFail();
        $this->assertStringNotContainsString('<script', $article->content);
        $this->assertStringContainsString('<p>Aman</p>', $article->content);
    }
}
