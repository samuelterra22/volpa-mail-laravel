<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\ContactList;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for contact lists (`/contact-lists` endpoint).
 *
 * Thin layer over {@see VolpaMailClient} that maps API responses to
 * {@see ContactList} DTOs. All HTTP goes through the injected client.
 */
final class ContactListResource
{
    public function __construct(
        private VolpaMailClient $client,
    ) {}

    /**
     * List all contact lists (`GET /contact-lists`).
     *
     * @return list<ContactList>
     *
     * @throws VolpaMailException
     */
    public function list(): array
    {
        $response = $this->client->get('/contact-lists');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data'] ?? [];

        return array_values(array_map(
            static fn (array $item): ContactList => ContactList::fromArray($item),
            $items,
        ));
    }

    /**
     * Create a new contact list (`POST /contact-lists`).
     *
     * @param  array{name: string, slug?: string, description?: string}  $data
     *
     * @throws VolpaMailException
     */
    public function create(array $data): ContactList
    {
        $response = $this->client->post('/contact-lists', $data);

        return ContactList::fromArray($response);
    }

    /**
     * Retrieve a single contact list by ID (`GET /contact-lists/{id}`).
     *
     * @throws VolpaMailException
     */
    public function get(string $id): ContactList
    {
        $response = $this->client->get("/contact-lists/{$id}");

        return ContactList::fromArray($response);
    }

    /**
     * Import contacts into a list (`POST /contact-lists/{id}/import`).
     *
     * @param  list<array{email: string, first_name?: string, last_name?: string, tags?: list<string>}>  $contacts
     * @return array{list_id: string, imported: int}
     *
     * @throws VolpaMailException
     */
    public function import(string $listId, array $contacts): array
    {
        /** @var array{list_id: string, imported: int} $response */
        $response = $this->client->post("/contact-lists/{$listId}/import", ['contacts' => $contacts]);

        return $response;
    }
}
