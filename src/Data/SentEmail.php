<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\EmailStatus;

/**
 * Immutable result of a send/lookup — mirrors the API response.
 *
 * The API returns `from` as a plain string (sender email only) and `to` as a
 * flat array of recipient email strings.
 */
final readonly class SentEmail
{
    /**
     * @param  list<string>  $to  Recipient email addresses.
     */
    public function __construct(
        public string $id,
        public EmailStatus $status,
        public ?string $createdAt = null,
        public ?string $from = null,
        public array $to = [],
        public ?string $subject = null,
        public ?string $messageStream = null,
    ) {}

    /**
     * Create from the API response; an unknown status falls back to {@see EmailStatus::Pending}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<string> $to */
        $to = array_values(array_map(
            static fn (mixed $address): string => (string) $address,
            is_array($data['to'] ?? null) ? $data['to'] : [],
        ));

        return new self(
            id: (string) ($data['id'] ?? ''),
            status: EmailStatus::tryFrom((string) ($data['status'] ?? '')) ?? EmailStatus::Pending,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            from: isset($data['from']) ? (string) $data['from'] : null,
            to: $to,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            messageStream: isset($data['message_stream']) ? (string) $data['message_stream'] : null,
        );
    }
}
