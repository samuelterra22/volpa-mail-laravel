<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\EmailStatus;

final readonly class SentEmail
{
    public function __construct(
        public string $id,
        public EmailStatus $status,
        public ?string $createdAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            status: EmailStatus::tryFrom((string) ($data['status'] ?? '')) ?? EmailStatus::Pending,
            createdAt: $data['created_at'] ?? null,
        );
    }
}
