<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Dua aturan keamanan login starter (di atas bawaan Breeze).
 */
class KeamananLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_tautan_reset_selalu_dari_app_url_walau_host_dipalsukan(): void
    {
        Notification::fake();
        config(['app.url' => 'https://tb-kel0.onrender.com']);
        $user = User::factory()->create();

        $this->withHeaders(['X-Forwarded-Host' => 'penyerang.test', 'Host' => 'penyerang.test'])
            ->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $url = $n->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://tb-kel0.onrender.com/reset-password/', $url);
            $this->assertStringNotContainsString('penyerang.test', $url);

            return true;
        });
    }

    public function test_login_terkunci_per_email_walau_ip_berganti(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
                ->post('/login', ['email' => $user->email, 'password' => 'salah'])
                ->assertSessionHasErrors('email');
        }

        // Percobaan ke-11 dari IP baru, password BENAR: tetap ditolak sementara.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reset_password_membuka_kunci_login_per_email(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.2.$i"])
                ->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'PasswordBaru-12345', 'password_confirmation' => 'PasswordBaru-12345',
        ])->assertSessionHasNoErrors();

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.3.1'])
            ->post('/login', ['email' => $user->email, 'password' => 'PasswordBaru-12345'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }
}
