<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

/**
 * Immutable DTO representing a Volpa Mail webhook event body.
 *
 * Shape: `{"type": "<event>", "created": "<ISO8601>", "data": { ... }}`.
 */
final readonly class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $data  Event-specific payload.
     */
    public function __construct(
        public string $type,
        public ?string $created,
        public array $data,
    ) {}

    /**
     * Build from a decoded array.
     *
     * @param  array<string, mixed>  $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            type: (string) ($array['type'] ?? ''),
            created: isset($array['created']) ? (string) $array['created'] : null,
            data: is_array($array['data'] ?? null) ? $array['data'] : [],
        );
    }

    /**
     * Build from a raw JSON string (the webhook request body).
     *
     * @throws \JsonException If the payload is not valid JSON.
     */
    public static function fromJson(string $payload): self
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return self::fromArray($decoded);
    }
}
