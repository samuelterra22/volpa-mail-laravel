<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable result of a bulk email validation request — mirrors the API response.
 */
final readonly class BulkValidationResult
{
    /**
     * @param  array<int, EmailValidationResult>  $data
     */
    public function __construct(
        public int $total,
        public int $valid,
        public array $data = [],
    ) {}

    /**
     * Create from the API response array.
     * Maps each item in 'data' through {@see EmailValidationResult::fromArray}.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var list<array<string, mixed>> $items */
        $items = $payload['data'] ?? [];

        return new self(
            total: (int) ($payload['total'] ?? 0),
            valid: (int) ($payload['valid'] ?? 0),
            data: array_map(
                static fn (array $item): EmailValidationResult => EmailValidationResult::fromArray($item),
                $items,
            ),
        );
    }
}
