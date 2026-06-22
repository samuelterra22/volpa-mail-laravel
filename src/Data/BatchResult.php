<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable result of a batch-send operation — mirrors the `POST /emails/batch` 202 response.
 */
final readonly class BatchResult
{
    public function __construct(
        public string $batchId,
        public int $totalQueued,
        public string $status,
        public ?string $createdAt = null,
    ) {}

    /**
     * Create from the API response array.
     *
     * Maps `batch_id` → `batchId`, `total_queued` → `totalQueued`,
     * `status` → `status`, `created_at` → `createdAt` (nullable).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            batchId: (string) ($data['batch_id'] ?? ''),
            totalQueued: (int) ($data['total_queued'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
