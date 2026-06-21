<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\BroadcastStatus;

/**
 * Immutable representation of a broadcast — mirrors the full API response shape.
 *
 * The index/list endpoint returns a partial shape (omits scheduled_at, total_delivered,
 * total_failed); all omitted fields are nullable and default to null so the same DTO
 * works for both list items and single-fetch results.
 */
final readonly class Broadcast
{
    public function __construct(
        public string $id,
        public string $name,
        public string $subject,
        public BroadcastStatus $status,
        public ?string $scheduledAt = null,
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public ?int $totalRecipients = null,
        public ?int $totalSent = null,
        public ?int $totalDelivered = null,
        public ?int $totalFailed = null,
    ) {}

    /**
     * Build from an API response array.
     *
     * Tolerates partial shapes returned by the list endpoint (missing keys fall back to null).
     * An unknown status value falls back to {@see BroadcastStatus::Draft}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            subject: (string) ($data['subject'] ?? ''),
            status: BroadcastStatus::tryFrom((string) ($data['status'] ?? '')) ?? BroadcastStatus::Draft,
            scheduledAt: isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
            startedAt: isset($data['started_at']) ? (string) $data['started_at'] : null,
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            totalRecipients: isset($data['total_recipients']) ? (int) $data['total_recipients'] : null,
            totalSent: isset($data['total_sent']) ? (int) $data['total_sent'] : null,
            totalDelivered: isset($data['total_delivered']) ? (int) $data['total_delivered'] : null,
            totalFailed: isset($data['total_failed']) ? (int) $data['total_failed'] : null,
        );
    }
}
