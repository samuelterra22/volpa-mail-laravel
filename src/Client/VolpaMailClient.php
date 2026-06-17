<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\Facades\VolpaMail;
use SamuelTerra\VolpaMail\VolpaMailServiceProvider;

/**
 * Low-level HTTP client for Volpa Mail.
 *
 * Centralizes all communication with the REST API: builds the authenticated
 * request (`X-API-Key` header), applies timeout and retry, and translates error
 * responses into {@see VolpaMailException}. It is the only place in the package
 * that talks HTTP — Resources and the Transport depend on it, never on `Http`
 * directly.
 *
 * Registered as a singleton by the {@see VolpaMailServiceProvider}
 * and exposed through the {@see VolpaMail} facade.
 */
final class VolpaMailClient
{
    /**
     * @param  HttpFactory  $http  Illuminate HTTP Client factory (injected so tests can use `Http::fake()`).
     * @param  string  $apiKey  Tenant key sent in the `X-API-Key` header.
     * @param  string  $baseUrl  API base URL, with or without a trailing slash.
     * @param  int  $timeout  Per-request timeout, in seconds.
     * @param  array{times: int, sleep: int}  $retry  Retry policy: number of attempts and wait (ms) between them.
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout = 10,
        private readonly array $retry = ['times' => 2, 'sleep' => 200],
    ) {}

    /**
     * Emails resource — entry point to send and look up messages.
     *
     * @see EmailResource
     */
    public function emails(): EmailResource
    {
        return new EmailResource($this);
    }

    /**
     * Perform an authenticated POST and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws VolpaMailException When the API key is missing or the API responds with an error (4xx/5xx).
     */
    public function post(string $uri, array $payload): array
    {
        return $this->send('post', $uri, $payload);
    }

    /**
     * Perform an authenticated GET and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws VolpaMailException When the API key is missing or the API responds with an error (4xx/5xx).
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->send('get', $uri, $query);
    }

    /**
     * Dispatch the HTTP request and normalize errors into {@see VolpaMailException}.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws VolpaMailException
     */
    private function send(string $method, string $uri, array $data): array
    {
        if ($this->apiKey === '') {
            throw VolpaMailException::missingApiKey();
        }

        $response = $this->request()->{$method}(ltrim($uri, '/'), $data);

        if ($response->failed()) {
            throw VolpaMailException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    /**
     * Build the base request, already authenticated and with timeout and retry applied.
     */
    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/').'/')
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'volpa-mail-laravel/1.0',
            ])
            ->timeout($this->timeout)
            ->retry($this->retry['times'], $this->retry['sleep'], throw: false)
            ->asJson();
    }
}
