<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Suppression;
use SamuelTerra\VolpaMail\Enums\SuppressionReason;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for suppression list management `/suppressions`.
 *
 * Provides CRUD operations plus bulk import over the Volpa Mail suppression
 * list. All HTTP communication goes through the injected {@see VolpaMailClient}.
 */
final class SuppressionResource
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {}

    /**
     * List suppression entries, optionally filtered.
     *
     * @param  array{reason?: string, source?: string, limit?: int}  $filters
     * @return list<Suppression>
     */
    public function list(array $filters = []): array
    {
        $query = array_filter([
            'reason' => $filters['reason'] ?? null,
            'source' => $filters['source'] ?? null,
            'limit' => $filters['limit'] ?? null,
        ], static fn (mixed $v): bool => $v !== null);

        $response = $this->client->get('/suppressions', $query);

        /** @var list<array<string, mixed>> $items */
        $items = $response['data'] ?? [];

        return array_map(
            static fn (array $item): Suppression => Suppression::fromArray($item),
            $items,
        );
    }

    /**
     * Add a single address to the suppression list.
     *
     * @throws VolpaMailException
     */
    public function create(string $email, SuppressionReason $reason): Suppression
    {
        $response = $this->client->post('/suppressions', [
            'email' => $email,
            'reason' => $reason->value,
        ]);

        return Suppression::fromArray($response);
    }

    /**
     * Retrieve a single suppression entry by email address.
     *
     * @throws VolpaMailException
     */
    public function get(string $email): Suppression
    {
        $response = $this->client->get('/suppressions/'.rawurlencode($email));

        return Suppression::fromArray($response);
    }

    /**
     * Remove an address from the suppression list (204 No Content on success).
     *
     * @throws VolpaMailException
     */
    public function delete(string $email): void
    {
        $this->client->delete('/suppressions/'.rawurlencode($email));
    }

    /**
     * Bulk-import a list of addresses into the suppression list.
     *
     * @param  list<string>  $emails
     * @return array{imported: int, reason: string}
     *
     * @throws VolpaMailException
     */
    public function import(array $emails, SuppressionReason $reason = SuppressionReason::Manual): array
    {
        /** @var array{imported: int, reason: string} */
        return $this->client->post('/suppressions/import', [
            'emails' => $emails,
            'reason' => $reason->value,
        ]);
    }
}
