<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use Symfony\Component\Mime\Address as SymfonyAddress;

/**
 * Email address (recipient or sender) with an optional display name.
 */
final readonly class Address
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}

    /**
     * Create from a Symfony Mime address.
     */
    public static function fromSymfony(SymfonyAddress $address): self
    {
        return new self(
            email: $address->getAddress(),
            name: $address->getName() !== '' ? $address->getName() : null,
        );
    }

    /**
     * Create from an array shaped like `['email' => ..., 'name' => ...]`.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            name: isset($data['name']) ? (string) $data['name'] : null,
        );
    }

    /**
     * Serialize to the API payload (omits an empty `name`).
     *
     * @return array{email: string, name?: string}
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'name' => $this->name,
        ], static fn ($value) => $value !== null);
    }
}
