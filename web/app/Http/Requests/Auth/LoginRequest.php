<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->throttleKeyEmail(), 600);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyEmail());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Dua batas: 5 kali per email + IP (bawaan Breeze), dan 10 kali per
        // email dari IP mana pun dalam 10 menit. Batas kedua perlu karena IP
        // bisa dipalsukan lewat X-Forwarded-For saat port aplikasi dibuka
        // langsung (codespace, laptop) dan karena penyerang bisa berganti IP.
        $kunci = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), 5) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->throttleKeyEmail(), 10) => $this->throttleKeyEmail(),
            default => null,
        };
        if ($kunci === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($kunci);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Kunci batas per email saja (tanpa IP).
     */
    public function throttleKeyEmail(): string
    {
        return self::kunciEmail((string) $this->string('email'));
    }

    /**
     * Kunci batas per email. Trade-off: orang lain bisa sengaja mengunci
     * akun selama 10 menit; pemilik tetap bisa masuk lewat reset password,
     * yang membuka kunci ini (NewPasswordController).
     */
    public static function kunciEmail(string $email): string
    {
        return 'login-email|'.Str::transliterate(Str::lower($email));
    }
}
