<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\LgpdErasure;
use SamuelTerra\VolpaMail\Data\LgpdExport;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * LGPD resource — data export and erasure endpoints.
 *
 * Both operations are synchronous (HTTP 200) and accept a single `email`
 * parameter, returning a structured payload described by the respective DTOs.
 */
final readonly class LgpdResource
{
    public function __construct(private VolpaMailClient $client) {}

    /**
     * Export all personal data held for the given email address.
     *
     * Corresponds to `POST /v1/lgpd/export`.
     *
     * @throws VolpaMailException When the API responds with an error (4xx/5xx).
     */
    public function export(string $email): LgpdExport
    {
        $response = $this->client->post('/lgpd/export', ['email' => $email]);

        return LgpdExport::fromArray($response);
    }

    /**
     * Erase all personal data held for the given email address.
     *
     * Corresponds to `POST /v1/lgpd/erasure`.
     *
     * @throws VolpaMailException When the API responds with an error (4xx/5xx).
     */
    public function erase(string $email): LgpdErasure
    {
        $response = $this->client->post('/lgpd/erasure', ['email' => $email]);

        return LgpdErasure::fromArray($response);
    }
}
