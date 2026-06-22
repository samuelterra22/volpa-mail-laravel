<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Data;

use SamuelTerra\VolpaMail\Enums\MemberRole;

/**
 * Immutable representation of a Volpa Mail tenant member — mirrors the API response.
 *
 * Only `id` and `role` are guaranteed across all response shapes (list, invite, update).
 * All other fields are nullable and will be null when absent from the response.
 */
final readonly class Member
{
    public function __construct(
        public string $id,
        public MemberRole $role,
        public ?string $userId = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?string $joinedAt = null,
        public ?string $invitedAt = null,
        public ?string $invitationExpiresAt = null,
    ) {}

    /**
     * Create from the API response array (snake_case keys).
     * Tolerates missing keys: only id and role are required.
     * Unknown role values fall back to {@see MemberRole::Viewer}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            role: MemberRole::tryFrom((string) ($data['role'] ?? '')) ?? MemberRole::Viewer,
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            joinedAt: isset($data['joined_at']) ? (string) $data['joined_at'] : null,
            invitedAt: isset($data['invited_at']) ? (string) $data['invited_at'] : null,
            invitationExpiresAt: isset($data['invitation_expires_at']) ? (string) $data['invitation_expires_at'] : null,
        );
    }
}
