<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Client\Resources;

use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Member;
use SamuelTerra\VolpaMail\Enums\MemberRole;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

/**
 * REST resource for tenant member management (`/members`).
 *
 * Thin layer over {@see VolpaMailClient} that maps API responses to {@see Member} DTOs.
 * Reached via `VolpaMail::members()` (requires wiring in {@see VolpaMailClient}).
 */
final class MemberResource
{
    public function __construct(
        private readonly VolpaMailClient $client,
    ) {}

    /**
     * List all tenant members (`GET /members`).
     *
     * @return array<int, Member>
     *
     * @throws VolpaMailException If the API returns an error.
     */
    public function list(): array
    {
        $response = $this->client->get('members');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data'] ?? [];

        return array_values(array_map(
            static fn (array $item): Member => Member::fromArray($item),
            $items,
        ));
    }

    /**
     * Invite a new member to the tenant (`POST /members`).
     *
     * @throws VolpaMailException If the member already exists (error code `already_member`)
     *                            or the API returns another error.
     */
    public function invite(string $email, MemberRole $role, ?string $name = null): Member
    {
        $response = $this->client->post('members', array_filter(
            ['email' => $email, 'role' => $role->value, 'name' => $name],
            static fn (mixed $v): bool => $v !== null,
        ));

        return Member::fromArray($response);
    }

    /**
     * Update a member's role (`PATCH /members/{id}`).
     *
     * @throws VolpaMailException If the member is not found (error code `not_found`).
     */
    public function updateRole(string $id, MemberRole $role): Member
    {
        $response = $this->client->patch("members/{$id}", ['role' => $role->value]);

        return Member::fromArray($response);
    }

    /**
     * Remove a member from the tenant (`DELETE /members/{id}`).
     *
     * @throws VolpaMailException If the member is not found (error code `not_found`).
     */
    public function remove(string $id): void
    {
        $this->client->delete("members/{$id}");
    }
}
