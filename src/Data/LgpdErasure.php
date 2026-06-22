<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable representation of a LGPD erasure response.
 */
final readonly class LgpdErasure
{
    /**
     * @param  array<string, int>  $stats  Row counts per entity erased (e.g. contact, memberships, …).
     */
    public function __construct(
        public bool $erased,
        public string $tenant,
        public string $email,
        public array $stats = [],
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            erased: (bool) ($data['erased'] ?? false),
            tenant: (string) ($data['tenant'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            stats: is_array($data['stats'] ?? null) ? $data['stats'] : [],
        );
    }
}
