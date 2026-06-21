<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\SuppressionReason;

/**
 * Immutable representation of a suppression entry returned by the API.
 */
final readonly class Suppression
{
    public function __construct(
        public string $id,
        public string $email,
        public SuppressionReason $reason,
        public ?string $source = null,
        public ?string $createdAt = null,
    ) {}

    /**
     * Create from the API response; an unknown reason falls back to {@see SuppressionReason::Manual}.
     *
     * @param  array{id?: mixed, email?: mixed, reason?: mixed, source?: mixed, created_at?: mixed}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            reason: SuppressionReason::tryFrom((string) ($data['reason'] ?? '')) ?? SuppressionReason::Manual,
            source: isset($data['source']) ? (string) $data['source'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
