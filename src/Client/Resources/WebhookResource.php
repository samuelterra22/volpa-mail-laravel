<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for webhook endpoint management (`/webhooks`).
 *
 * Reached via `VolpaMail::webhooks()`. All HTTP goes through
 * {@see VolpaMailClient} — never the Http facade directly.
 */
final readonly class WebhookResource
{
    public function __construct(
        private VolpaMailClient $client,
    ) {}

    /**
     * List all registered webhook endpoints (`GET /webhooks`).
     *
     * @return list<array<string, mixed>>
     *
     * @throws VolpaMailException
     */
    public function list(): array
    {
        $response = $this->client->get('webhooks');

        /** @var list<array<string, mixed>> $data */
        $data = $response['data'] ?? [];

        return $data;
    }

    /**
     * Register a new webhook endpoint (`POST /webhooks`).
     *
     * @param  array{url: string, events: list<string>, description?: string}  $data
     * @return array<string, mixed> Response includes `{id, url, events, secret, created_at}`.
     *
     * @throws VolpaMailException
     */
    public function create(array $data): array
    {
        return $this->client->post('webhooks', $data);
    }

    /**
     * Retrieve details of a single webhook endpoint (`GET /webhooks/{id}`).
     *
     * @return array<string, mixed> Includes `total_deliveries`, `last_failure_at`, etc.
     *
     * @throws VolpaMailException
     */
    public function get(string $id): array
    {
        return $this->client->get("webhooks/{$id}");
    }

    /**
     * Delete a webhook endpoint (`DELETE /webhooks/{id}` → 204).
     *
     * @throws VolpaMailException
     */
    public function delete(string $id): void
    {
        $this->client->delete("webhooks/{$id}");
    }

    /**
     * Trigger a test delivery for a webhook endpoint (`POST /webhooks/{id}/test`).
     *
     * @return array<string, mixed> Shape: `{queued: true}`.
     *
     * @throws VolpaMailException
     */
    public function test(string $id): array
    {
        return $this->client->post("webhooks/{$id}/test", []);
    }
}
