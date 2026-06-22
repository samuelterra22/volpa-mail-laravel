<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use SamuelTerra\VolpaMail\Client\Resources\BroadcastResource;
use SamuelTerra\VolpaMail\Client\Resources\ContactListResource;
use SamuelTerra\VolpaMail\Client\Resources\ContactResource;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Client\Resources\LgpdResource;
use SamuelTerra\VolpaMail\Client\Resources\MemberResource;
use SamuelTerra\VolpaMail\Client\Resources\SuppressionResource;
use SamuelTerra\VolpaMail\Client\Resources\ValidationResource;
use SamuelTerra\VolpaMail\Client\Resources\WebhookResource;
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
     * Suppressions resource — manage the suppression list.
     *
     * @see SuppressionResource
     */
    public function suppressions(): SuppressionResource
    {
        return new SuppressionResource($this);
    }

    /**
     * Contacts resource — create and look up contacts.
     *
     * @see ContactResource
     */
    public function contacts(): ContactResource
    {
        return new ContactResource($this);
    }

    /**
     * Contact lists resource — manage lists and bulk imports.
     *
     * @see ContactListResource
     */
    public function contactLists(): ContactListResource
    {
        return new ContactListResource($this);
    }

    /**
     * Broadcasts resource — create, send and cancel broadcasts.
     *
     * @see BroadcastResource
     */
    public function broadcasts(): BroadcastResource
    {
        return new BroadcastResource($this);
    }

    /**
     * Webhooks resource — register and manage webhook endpoints.
     *
     * @see WebhookResource
     */
    public function webhooks(): WebhookResource
    {
        return new WebhookResource($this);
    }

    /**
     * Email validation resource — single and bulk address validation.
     *
     * @see ValidationResource
     */
    public function validation(): ValidationResource
    {
        return new ValidationResource($this);
    }

    /**
     * LGPD resource — data subject export and erasure.
     *
     * @see LgpdResource
     */
    public function lgpd(): LgpdResource
    {
        return new LgpdResource($this);
    }

    /**
     * Tenant members resource — list, invite, update role and remove members.
     *
     * @see MemberResource
     */
    public function members(): MemberResource
    {
        return new MemberResource($this);
    }

    /**
     * Perform an authenticated POST and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers  Extra request headers (e.g. `Idempotency-Key`).
     * @return array<string, mixed>
     *
     * @throws VolpaMailException When the API key is missing or the API responds with an error (4xx/5xx).
     */
    public function post(string $uri, array $payload, array $headers = []): array
    {
        return $this->send('post', $uri, $payload, $headers);
    }

    /**
     * Perform an authenticated PATCH and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws VolpaMailException When the API key is missing or the API responds with an error (4xx/5xx).
     */
    public function patch(string $uri, array $payload, array $headers = []): array
    {
        return $this->send('patch', $uri, $payload, $headers);
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
     * Perform an authenticated DELETE and return the decoded JSON body (may be empty).
     *
     * @return array<string, mixed>
     *
     * @throws VolpaMailException When the API key is missing or the API responds with an error (4xx/5xx).
     */
    public function delete(string $uri): array
    {
        return $this->send('delete', $uri, []);
    }

    /**
     * Dispatch the HTTP request and normalize errors into {@see VolpaMailException}.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws VolpaMailException
     */
    private function send(string $method, string $uri, array $data, array $headers = []): array
    {
        if ($this->apiKey === '') {
            throw VolpaMailException::missingApiKey();
        }

        $response = $this->request($headers)->{$method}(ltrim($uri, '/'), $data);

        if ($response->failed()) {
            throw VolpaMailException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    /**
     * Build the base request, already authenticated and with timeout and retry applied.
     *
     * @param  array<string, string>  $headers  Extra headers merged after the defaults.
     */
    private function request(array $headers = []): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/').'/')
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'volpa-mail-laravel/1.0',
                ...$headers,
            ])
            ->timeout($this->timeout)
            ->retry($this->retry['times'], $this->retry['sleep'], throw: false)
            ->asJson();
    }
}
