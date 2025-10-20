<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TwitterAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TwitterAccountController extends Controller
{
    private const AUTHORIZE_URL = 'https://twitter.com/i/oauth2/authorize';
    private const TOKEN_URL = 'https://api.twitter.com/2/oauth2/token';
    private const USER_URL = 'https://api.twitter.com/2/users/me';

    public function index(): Response
    {
        $accounts = TwitterAccount::query()->latest('connected_at')->get();

        return response()->json([
            'data' => $accounts->map(fn (TwitterAccount $account): array => $this->transform($account))->all(),
        ]);
    }

    public function redirect(Request $request): Response
    {
        $config = $this->twitterConfig();

        $state = (string) Str::uuid();
        $codeVerifier = Str::random(96);
        $codeChallenge = $this->codeChallenge($codeVerifier);

        Cache::put(
            $this->cacheKey($state),
            [
                'user_id' => $request->user()->id,
                'workspace_id' => currentWorkspace()->id,
                'code_verifier' => $codeVerifier,
            ],
            now()->addMinutes(10),
        );

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return response()->json([
            'data' => [
                'authorization_url' => sprintf('%s?%s', self::AUTHORIZE_URL, $query),
                'state' => $state,
            ],
        ]);
    }

    public function callback(Request $request): Response
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        $stateData = Cache::pull($this->cacheKey($data['state']));

        if ($stateData === null) {
            throw ValidationException::withMessages([
                'state' => ['The provided state is invalid or has expired.'],
            ]);
        }

        if ((int) $stateData['user_id'] !== $request->user()->id || (int) $stateData['workspace_id'] !== currentWorkspace()->id) {
            throw ValidationException::withMessages([
                'state' => ['The provided state does not match the current session.'],
            ]);
        }

        $config = $this->twitterConfig();

        $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $data['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'],
            'code_verifier' => $stateData['code_verifier'],
        ]);

        if ($tokenResponse->failed()) {
            return response()->json([
                'errors' => [[
                    'status' => '400',
                    'title' => 'Unable to exchange authorization code for access token.',
                    'detail' => $tokenResponse->json('error_description') ?? $tokenResponse->body(),
                ]],
            ], 400);
        }

        $tokenData = $tokenResponse->json();

        $userResponse = Http::withToken($tokenData['access_token'])
            ->get(self::USER_URL, [
                'user.fields' => 'name,username,profile_image_url',
            ]);

        if ($userResponse->failed()) {
            return response()->json([
                'errors' => [[
                    'status' => '400',
                    'title' => 'Unable to retrieve Twitter account information.',
                    'detail' => $userResponse->json('error') ?? $userResponse->body(),
                ]],
            ], 400);
        }

        $userData = $userResponse->json('data', []);

        $twitterId = Arr::get($userData, 'id');

        if ($twitterId === null) {
            return response()->json([
                'errors' => [[
                    'status' => '400',
                    'title' => 'Twitter did not return an account identifier.',
                ]],
            ], 400);
        }

        $account = TwitterAccount::updateOrCreate(
            [
                'workspace_id' => currentWorkspace()->id,
                'twitter_id' => $twitterId,
            ],
            [
                'user_id' => $request->user()->id,
                'username' => Arr::get($userData, 'username'),
                'name' => Arr::get($userData, 'name'),
                'profile_image_url' => Arr::get($userData, 'profile_image_url'),
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : null,
                'scopes' => isset($tokenData['scope']) ? explode(' ', (string) $tokenData['scope']) : $config['scopes'],
                'connected_at' => now(),
                'disconnected_at' => null,
            ],
        );

        return response()->json([
            'data' => $this->transform($account->fresh()),
        ]);
    }

    public function refresh(Request $request): Response
    {
        $data = $request->validate([
            'twitter_account_id' => [
                'required',
                'integer',
                Rule::exists('twitter_accounts', 'id')->where(fn ($query) => $query->where('workspace_id', currentWorkspace()->id)),
            ],
        ]);

        /** @var TwitterAccount $account */
        $account = TwitterAccount::query()->findOrFail($data['twitter_account_id']);

        if (null === $account->refresh_token) {
            return response()->json([
                'errors' => [[
                    'status' => '400',
                    'title' => 'This Twitter account does not have a refresh token.',
                ]],
            ], 400);
        }

        $config = $this->twitterConfig();

        $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        if ($tokenResponse->failed()) {
            return response()->json([
                'errors' => [[
                    'status' => '400',
                    'title' => 'Unable to refresh access token.',
                    'detail' => $tokenResponse->json('error_description') ?? $tokenResponse->body(),
                ]],
            ], 400);
        }

        $tokenData = $tokenResponse->json();

        $account->forceFill([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : null,
            'scopes' => isset($tokenData['scope']) ? explode(' ', (string) $tokenData['scope']) : $account->scopes,
            'disconnected_at' => null,
        ])->save();

        return response()->json([
            'data' => $this->transform($account->fresh()),
        ]);
    }

    public function destroy(TwitterAccount $twitterAccount): Response
    {
        $twitterAccount->forceFill([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'disconnected_at' => now(),
        ])->save();

        return response()->json([
            'data' => $this->transform($twitterAccount->fresh()),
        ]);
    }

    private function twitterConfig(): array
    {
        $config = Config::get('services.twitter');

        if (! is_array($config)) {
            $config = [];
        }

        $required = ['client_id', 'client_secret', 'redirect_uri'];

        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw ValidationException::withMessages([
                    'twitter' => [sprintf('Twitter configuration is missing the %s value.', $key)],
                ]);
            }
        }

        $config['scopes'] = array_values(array_filter($config['scopes'] ?? []));

        return $config;
    }

    private function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function cacheKey(string $state): string
    {
        return sprintf('twitter-oauth:%s', $state);
    }

    private function transform(TwitterAccount $account): array
    {
        return [
            'id' => $account->id,
            'workspace_id' => $account->workspace_id,
            'user_id' => $account->user_id,
            'twitter_id' => $account->twitter_id,
            'username' => $account->username,
            'name' => $account->name,
            'profile_image_url' => $account->profile_image_url,
            'scopes' => $account->scopes,
            'token_expires_at' => $account->token_expires_at?->toIso8601String(),
            'connected_at' => $account->connected_at?->toIso8601String(),
            'disconnected_at' => $account->disconnected_at?->toIso8601String(),
            'status' => $account->status,
            'is_connected' => $account->is_connected,
        ];
    }
}
