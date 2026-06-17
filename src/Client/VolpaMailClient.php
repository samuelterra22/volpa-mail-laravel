<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

final class VolpaMailClient
{
    /**
     * @param  array{times: int, sleep: int}  $retry
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout = 10,
        private readonly array $retry = ['times' => 2, 'sleep' => 200],
    ) {}

    public function emails(): EmailResource
    {
        return new EmailResource($this);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $uri, array $payload): array
    {
        return $this->send('post', $uri, $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->send('get', $uri, $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
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
