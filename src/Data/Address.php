<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use Symfony\Component\Mime\Address as SymfonyAddress;

final readonly class Address
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {
    }

    public static function fromSymfony(SymfonyAddress $address): self
    {
        return new self(
            email: $address->getAddress(),
            name: $address->getName() !== '' ? $address->getName() : null,
        );
    }

    /**
     * @param  array{email: string, name?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            name: $data['name'] ?? null,
        );
    }

    /**
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
