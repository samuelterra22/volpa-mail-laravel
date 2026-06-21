<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\ContactStatus;

/**
 * Immutable representation of a Volpa Mail contact — mirrors the API response.
 */
final readonly class Contact
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $id,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ContactStatus $status,
        public array $tags = [],
        public array $attributes = [],
        public ?string $subscribedAt = null,
        public ?string $createdAt = null,
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     * Tolerates missing keys: tags, attributes, and created_at are optional.
     * Unknown status values fall back to {@see ContactStatus::Active}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            firstName: isset($data['first_name']) ? (string) $data['first_name'] : null,
            lastName: isset($data['last_name']) ? (string) $data['last_name'] : null,
            status: ContactStatus::tryFrom((string) ($data['status'] ?? '')) ?? ContactStatus::Active,
            tags: array_values(array_map('strval', (array) ($data['tags'] ?? []))),
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
            subscribedAt: isset($data['subscribed_at']) ? (string) $data['subscribed_at'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
