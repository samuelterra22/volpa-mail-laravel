<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable representation of a LGPD data-export response.
 *
 * Nested collections (emails, events, suppressions, unsubscribe_links) are kept
 * as plain arrays to avoid building a full DTO graph for rarely-consumed data.
 */
final readonly class LgpdExport
{
    /**
     * @param  array<string, mixed>|null  $contact
     * @param  array<int, array<string, mixed>>  $emails
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<int, array<string, mixed>>  $suppressions
     * @param  array<int, array<string, mixed>>  $unsubscribeLinks
     */
    public function __construct(
        public string $tenant,
        public string $email,
        public ?string $exportedAt = null,
        public ?array $contact = null,
        public array $emails = [],
        public array $events = [],
        public array $suppressions = [],
        public array $unsubscribeLinks = [],
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant: (string) ($data['tenant'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            exportedAt: isset($data['exported_at']) ? (string) $data['exported_at'] : null,
            contact: is_array($data['contact'] ?? null) ? $data['contact'] : null,
            emails: is_array($data['emails'] ?? null) ? array_values($data['emails']) : [],
            events: is_array($data['events'] ?? null) ? array_values($data['events']) : [],
            suppressions: is_array($data['suppressions'] ?? null) ? array_values($data['suppressions']) : [],
            unsubscribeLinks: is_array($data['unsubscribe_links'] ?? null) ? array_values($data['unsubscribe_links']) : [],
        );
    }
}
