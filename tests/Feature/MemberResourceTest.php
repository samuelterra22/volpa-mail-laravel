<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\MemberResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Member;
use SamuelTerra\VolpaMail\Enums\MemberRole;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeMemberClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('lists members and returns array of Member DTOs', function () {
    Http::fake([
        '*/members' => Http::response([
            'data' => [
                [
                    'id' => 'mem_1',
                    'user_id' => 'usr_1',
                    'email' => 'alice@example.com',
                    'name' => 'Alice',
                    'role' => 'admin',
                    'joined_at' => '2026-01-01T00:00:00Z',
                ],
                [
                    'id' => 'mem_2',
                    'user_id' => 'usr_2',
                    'email' => 'bob@example.com',
                    'name' => 'Bob',
                    'role' => 'viewer',
                    'joined_at' => '2026-02-01T00:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $resource = new MemberResource(makeMemberClient());
    $members = $resource->list();

    expect($members)->toHaveCount(2)
        ->and($members[0])->toBeInstanceOf(Member::class)
        ->and($members[0]->id)->toBe('mem_1')
        ->and($members[0]->email)->toBe('alice@example.com')
        ->and($members[0]->role)->toBe(MemberRole::Admin)
        ->and($members[0]->role)->toBeInstanceOf(MemberRole::class)
        ->and($members[1]->role)->toBe(MemberRole::Viewer);
});

it('invites a member and returns Member DTO with correct body', function () {
    Http::fake([
        '*/members' => Http::response([
            'id' => 'mem_new',
            'user_id' => null,
            'email' => 'carol@example.com',
            'role' => 'developer',
            'invited_at' => '2026-06-01T00:00:00Z',
            'invitation_expires_at' => '2026-06-08T00:00:00Z',
        ], 201),
    ]);

    $resource = new MemberResource(makeMemberClient());
    $member = $resource->invite('carol@example.com', MemberRole::Developer, 'Carol');

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->id)->toBe('mem_new')
        ->and($member->email)->toBe('carol@example.com')
        ->and($member->role)->toBe(MemberRole::Developer)
        ->and($member->invitedAt)->toBe('2026-06-01T00:00:00Z')
        ->and($member->invitationExpiresAt)->toBe('2026-06-08T00:00:00Z');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/members')
            && $request->method() === 'POST'
            && $body['email'] === 'carol@example.com'
            && $body['role'] === 'developer'
            && $body['name'] === 'Carol';
    });
});

it('updates a member role and returns Member DTO', function () {
    Http::fake([
        '*/members/mem_1' => Http::response([
            'id' => 'mem_1',
            'role' => 'admin',
        ], 200),
    ]);

    $resource = new MemberResource(makeMemberClient());
    $member = $resource->updateRole('mem_1', MemberRole::Admin);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->id)->toBe('mem_1')
        ->and($member->role)->toBe(MemberRole::Admin);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/members/mem_1')
            && $request->method() === 'PATCH'
            && $request->data()['role'] === 'admin';
    });
});

it('removes a member without throwing an exception', function () {
    Http::fake([
        '*/members/mem_1' => Http::response(null, 204),
    ]);

    $resource = new MemberResource(makeMemberClient());

    expect(fn () => $resource->remove('mem_1'))->not->toThrow(VolpaMailException::class);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/members/mem_1')
            && $request->method() === 'DELETE';
    });
});

it('throws VolpaMailException on 409 already_member during invite', function () {
    Http::fake([
        '*/members' => Http::response([
            'error' => ['code' => 'already_member'],
        ], 409),
    ]);

    $resource = new MemberResource(makeMemberClient());
    $resource->invite('existing@example.com', MemberRole::Viewer);
})->throws(VolpaMailException::class);

it('Member::fromArray tolerates missing optional fields', function () {
    $member = Member::fromArray([
        'id' => 'mem_min',
        'role' => 'owner',
    ]);

    expect($member->id)->toBe('mem_min')
        ->and($member->role)->toBe(MemberRole::Owner)
        ->and($member->userId)->toBeNull()
        ->and($member->email)->toBeNull()
        ->and($member->name)->toBeNull()
        ->and($member->joinedAt)->toBeNull()
        ->and($member->invitedAt)->toBeNull()
        ->and($member->invitationExpiresAt)->toBeNull();
});

it('Member::fromArray falls back to Viewer for unknown role', function () {
    $member = Member::fromArray([
        'id' => 'mem_x',
        'role' => 'unknown_role',
    ]);

    expect($member->role)->toBe(MemberRole::Viewer);
});
