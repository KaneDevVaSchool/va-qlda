<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
    /**
     * SPA: whether Google sign-in is configured.
     */
    public function config()
    {
        $id = config('services.google.client_id');
        $secret = config('services.google.client_secret');

        return response()->json([
            'enabled' => is_string($id) && $id !== '' && is_string($secret) && $secret !== '',
        ]);
    }

    /**
     * SPA: URL to send the browser to (Google consent screen).
     */
    public function redirectUrl()
    {
        $this->assertGoogleConfigured();

        $url = Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * OAuth redirect target (must match Google Cloud Console).
     */
    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            $code = 'oauth_denied';

            return redirect()->to($this->loginUrl(['google_error' => $code]));
        }

        try {
            $this->assertGoogleConfigured();
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->to($this->loginUrl(['google_error' => 'oauth_failed']));
        }

        $email = $googleUser->getEmail();
        if (! is_string($email) || trim($email) === '') {
            return redirect()->to($this->loginUrl(['google_error' => 'no_email']));
        }

        $email = strtolower(trim($email));
        $googleId = (string) $googleUser->getId();
        $name = $googleUser->getName() ?: (strstr($email, '@', true) ?: $email);

        if (! $this->googleEmailDomainAllowed($email)) {
            return redirect()->to($this->loginUrl(['google_error' => 'domain_not_allowed']));
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user) {
                if ($user->google_id !== null && $user->google_id !== $googleId) {
                    return redirect()->to($this->loginUrl(['google_error' => 'account_mismatch']));
                }
                $user->forceFill(['google_id' => $googleId])->save();
            }
        }

        if (! $user) {
            if (! config('services.google.allow_register')) {
                return redirect()->to($this->loginUrl(['google_error' => 'no_account']));
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Str::password(32),
                'role' => 'developer',
                'google_id' => $googleId,
            ]);
        }

        if ($user->isLocked()) {
            return redirect()->to($this->loginUrl(['google_error' => 'locked']));
        }

        $this->writeLoginHistory($request, $user, LoginHistory::EVENT_LOGIN_SUCCESS, [
            'via' => 'google_oauth',
        ]);

        $token = $user->createToken('ppms-spa')->plainTextToken;
        $exchange = Str::random(48);
        Cache::put($this->cacheKey($exchange), [
            'token' => $token,
            'user_id' => $user->id,
        ], now()->addMinutes(5));

        return redirect()->to($this->loginUrl(['google_exchange' => $exchange]));
    }

    /**
     * SPA: exchange one-time key from callback redirect for API token + user.
     */
    public function exchange(Request $request)
    {
        $data = $request->validate([
            'exchange' => 'required|string|size:48',
        ]);

        $payload = Cache::pull($this->cacheKey($data['exchange']));
        if (! is_array($payload) || empty($payload['token']) || empty($payload['user_id'])) {
            throw ValidationException::withMessages([
                'exchange' => ['This sign-in link has expired. Please try Google sign-in again.'],
            ]);
        }

        $user = User::query()->find($payload['user_id']);
        if (! $user) {
            throw ValidationException::withMessages([
                'exchange' => ['This sign-in link has expired. Please try Google sign-in again.'],
            ]);
        }

        return response()->json([
            'token' => $payload['token'],
            'user' => $user,
        ]);
    }

    private function assertGoogleConfigured(): void
    {
        $id = config('services.google.client_id');
        $secret = config('services.google.client_secret');
        if (! is_string($id) || $id === '' || ! is_string($secret) || $secret === '') {
            abort(503, 'Google OAuth is not configured.');
        }
    }

    /**
     * When ACCEPT_DOMAIN_MAIL / config ppms.google_allowed_email_domains is non-empty,
     * the email's domain (after @) must match one entry exactly (case-insensitive).
     */
    private function googleEmailDomainAllowed(string $email): bool
    {
        $allowed = config('ppms.google_allowed_email_domains', []);
        if (! is_array($allowed) || $allowed === []) {
            return true;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        if ($domain === '') {
            return false;
        }

        foreach ($allowed as $entry) {
            if (! is_string($entry) || $entry === '') {
                continue;
            }
            if (strtolower($entry) === $domain) {
                return true;
            }
        }

        return false;
    }

    private function cacheKey(string $exchange): string
    {
        return 'google_oauth_exchange:'.$exchange;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function loginUrl(array $query): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base.'/login?'.http_build_query($query);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function writeLoginHistory(Request $request, User $user, string $event, array $meta = []): void
    {
        try {
            LoginHistory::query()->create([
                'user_id' => $user->id,
                'ip_address' => $request->ip() ?: '0.0.0.0',
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => hash('sha256', ($request->userAgent() ?? '').'|'.($request->ip() ?? '')),
                'event' => $event,
                'meta' => $meta,
            ]);
        } catch (Throwable) {
            //
        }
    }
}
