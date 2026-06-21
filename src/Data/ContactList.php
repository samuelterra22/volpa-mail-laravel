<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable representation of a Volpa Mail contact list — mirrors the API response.
 */
final readonly class ContactList
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?int $totalContacts,
        public ?int $totalSubscribed,
        public ?string $createdAt,
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            totalContacts: isset($data['total_contacts']) ? (int) $data['total_contacts'] : null,
            totalSubscribed: isset($data['total_subscribed']) ? (int) $data['total_subscribed'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
