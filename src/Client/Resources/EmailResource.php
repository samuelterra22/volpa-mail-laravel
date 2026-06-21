<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Data\SentEmail;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for transactional emails (the `/emails` endpoint).
 *
 * Thin layer over {@see VolpaMailClient} that turns DTOs into the API payload
 * and responses into {@see SentEmail}. Reached via `VolpaMail::emails()`.
 */
final readonly class EmailResource
{
    public function __construct(
        private VolpaMailClient $client,
    ) {}

    /**
     * Send a transactional email (`POST /emails`).
     *
     * Accepts a typed {@see SendEmailData} or a "friendly" array, which is
     * converted by {@see SendEmailData::fromArray()}.
     *
     * Pass an `$idempotencyKey` (e.g. a UUID v7) to make retries safe: the API
     * replays the original response for a repeated key within its TTL and
     * returns 409 if the same key is reused with a different body.
     *
     * @param  SendEmailData|array<string, mixed>  $email
     * @param  string|null  $idempotencyKey  Sent as the `Idempotency-Key` header when provided.
     *
     * @throws VolpaMailException If a required field is missing or the API returns an error.
     */
    public function send(SendEmailData|array $email, ?string $idempotencyKey = null): SentEmail
    {
        $data = $email instanceof SendEmailData
            ? $email
            : SendEmailData::fromArray($email);

        $headers = $idempotencyKey !== null ? ['Idempotency-Key' => $idempotencyKey] : [];

        $response = $this->client->post('emails', $data->toArray(), $headers);

        return SentEmail::fromArray($response);
    }

    /**
     * Look up the current status of an email by ID (`GET /emails/{id}`).
     *
     * @param  string  $id  Identifier returned on send (e.g. `eml_123`).
     *
     * @throws VolpaMailException If the API returns an error.
     */
    public function get(string $id): SentEmail
    {
        $response = $this->client->get("emails/{$id}");

        return SentEmail::fromArray($response);
    }
}
