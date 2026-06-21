<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Broadcast;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for bulk broadcasts (`/broadcasts`).
 *
 * Thin layer over {@see VolpaMailClient} that maps API responses to {@see Broadcast} DTOs.
 * Reached via `VolpaMail::broadcasts()` (requires wiring in {@see VolpaMailClient}).
 */
final class BroadcastResource
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {}

    /**
     * List all broadcasts (`GET /broadcasts`).
     *
     * The index shape is partial — it omits scheduled_at, total_delivered, and total_failed.
     * Those fields will be null on the returned DTOs.
     *
     * @return list<Broadcast>
     *
     * @throws VolpaMailException If the API returns an error.
     */
    public function list(): array
    {
        $response = $this->client->get('broadcasts');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data'] ?? [];

        return array_values(array_map(
            static fn (array $item): Broadcast => Broadcast::fromArray($item),
            $items,
        ));
    }

    /**
     * Create a new broadcast (`POST /broadcasts`).
     *
     * Pass `scheduled_at` (ISO-8601, future) to schedule instead of saving as draft.
     *
     * @param  array{
     *     name: string,
     *     sender_id: string,
     *     subject: string,
     *     template_id?: string,
     *     message_stream_id?: string,
     *     html_body?: string,
     *     text_body?: string,
     *     reply_to?: string,
     *     contact_list_ids?: list<string>,
     *     excluded_list_ids?: list<string>,
     *     segment_filters?: array<mixed>,
     *     scheduled_at?: string,
     * }  $data
     *
     * @throws VolpaMailException If validation fails or the sender is not found.
     */
    public function create(array $data): Broadcast
    {
        $response = $this->client->post('broadcasts', $data);

        return Broadcast::fromArray($response);
    }

    /**
     * Fetch a single broadcast by ID (`GET /broadcasts/{id}`).
     *
     * Returns the full shape including all counters and timestamps.
     *
     * @throws VolpaMailException If the broadcast is not found or the API returns an error.
     */
    public function get(string $id): Broadcast
    {
        $response = $this->client->get("broadcasts/{$id}");

        return Broadcast::fromArray($response);
    }

    /**
     * Trigger immediate sending of a draft or scheduled broadcast (`POST /broadcasts/{id}/send`).
     *
     * Returns the raw decoded response body `{id, status, total_queued}` rather than a full
     * {@see Broadcast} because the API returns a different (lighter) shape on this endpoint.
     *
     * @return array{id: string, status: string, total_queued: int}
     *
     * @throws VolpaMailException If the broadcast is not found or has already been finalized
     *                            (error code `broadcast_finalized`).
     */
    public function send(string $id): array
    {
        /** @var array{id: string, status: string, total_queued: int} */
        return $this->client->post("broadcasts/{$id}/send", []);
    }

    /**
     * Cancel a broadcast that has not yet been finalized (`POST /broadcasts/{id}/cancel`).
     *
     * @throws VolpaMailException If the broadcast cannot be canceled (error code `cannot_cancel`)
     *                            or is not found.
     */
    public function cancel(string $id): Broadcast
    {
        $response = $this->client->post("broadcasts/{$id}/cancel", []);

        return Broadcast::fromArray($response);
    }
}
