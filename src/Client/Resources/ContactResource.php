<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Contact;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for contacts (`/contacts` endpoint).
 *
 * Thin layer over {@see VolpaMailClient} that maps API responses to
 * {@see Contact} DTOs. All HTTP goes through the injected client.
 */
final class ContactResource
{
    public function __construct(
        private VolpaMailClient $client,
    ) {}

    /**
     * List contacts, optionally filtered.
     *
     * @param  array{status?: string, limit?: int}  $filters
     * @return list<Contact>
     *
     * @throws VolpaMailException
     */
    public function list(array $filters = []): array
    {
        $response = $this->client->get('/contacts', $filters);

        /** @var list<array<string, mixed>> $items */
        $items = $response['data'] ?? [];

        return array_values(array_map(
            static fn (array $item): Contact => Contact::fromArray($item),
            $items,
        ));
    }

    /**
     * Create a new contact (`POST /contacts`).
     *
     * @param  array{email: string, first_name?: string, last_name?: string, tags?: list<string>, attributes?: array<string, mixed>, list_ids?: list<string>}  $data
     *
     * @throws VolpaMailException
     */
    public function create(array $data): Contact
    {
        $response = $this->client->post('/contacts', $data);

        return Contact::fromArray($response);
    }

    /**
     * Retrieve a single contact by ID (`GET /contacts/{id}`).
     *
     * @throws VolpaMailException
     */
    public function get(string $id): Contact
    {
        $response = $this->client->get("/contacts/{$id}");

        return Contact::fromArray($response);
    }
}
