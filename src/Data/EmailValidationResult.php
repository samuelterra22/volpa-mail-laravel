<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\ValidationReason;

/**
 * Immutable result of a single email validation request — mirrors the API response.
 */
final readonly class EmailValidationResult
{
    /**
     * @param  array<string, bool>  $checks
     */
    public function __construct(
        public string $email,
        public bool $valid,
        public ?ValidationReason $reason,
        public array $checks = [],
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     * Tolerates missing keys: checks is optional.
     * Unknown reason values fall back to null.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            reason: isset($data['reason']) ? ValidationReason::tryFrom((string) $data['reason']) : null,
            checks: is_array($data['checks'] ?? null) ? $data['checks'] : [],
        );
    }
}
