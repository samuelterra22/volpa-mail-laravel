<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\ContactResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Contact;
use SamuelTerra\VolpaMail\Enums\ContactStatus;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeContactClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('lists contacts and returns array of Contact DTOs', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'data' => [
                [
                    'id' => 'cnt_1',
                    'email' => 'alice@example.com',
                    'first_name' => 'Alice',
                    'last_name' => 'Smith',
                    'status' => 'active',
                    'subscribed_at' => '2026-01-01T00:00:00Z',
                ],
                [
                    'id' => 'cnt_2',
                    'email' => 'bob@example.com',
                    'first_name' => null,
                    'last_name' => null,
                    'status' => 'unsubscribed',
                    'subscribed_at' => null,
                ],
            ],
        ], 200),
    ]);

    $resource = new ContactResource(makeContactClient());
    $contacts = $resource->list();

    expect($contacts)->toHaveCount(2)
        ->and($contacts[0])->toBeInstanceOf(Contact::class)
        ->and($contacts[0]->id)->toBe('cnt_1')
        ->and($contacts[0]->email)->toBe('alice@example.com')
        ->and($contacts[0]->firstName)->toBe('Alice')
        ->and($contacts[0]->status)->toBe(ContactStatus::Active)
        ->and($contacts[1]->status)->toBe(ContactStatus::Unsubscribed);
});

it('sends status filter as query parameter', function () {
    Http::fake([
        '*/contacts*' => Http::response(['data' => []], 200),
    ]);

    $resource = new ContactResource(makeContactClient());
    $resource->list(['status' => 'bounced', 'limit' => 10]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'status=bounced')
            && str_contains($request->url(), 'limit=10');
    });
});

it('creates a contact and returns Contact DTO', function () {
    Http::fake([
        '*/contacts' => Http::response([
            'id' => 'cnt_new',
            'email' => 'new@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'status' => 'active',
            'tags' => ['newsletter'],
            'attributes' => ['plan' => 'pro'],
            'subscribed_at' => '2026-06-20T00:00:00Z',
            'created_at' => '2026-06-20T00:00:00Z',
        ], 201),
    ]);

    $resource = new ContactResource(makeContactClient());
    $contact = $resource->create([
        'email' => 'new@example.com',
        'first_name' => 'New',
        'last_name' => 'User',
        'tags' => ['newsletter'],
    ]);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->id)->toBe('cnt_new')
        ->and($contact->email)->toBe('new@example.com')
        ->and($contact->firstName)->toBe('New')
        ->and($contact->tags)->toBe(['newsletter'])
        ->and($contact->attributes)->toBe(['plan' => 'pro'])
        ->and($contact->status)->toBe(ContactStatus::Active);
});

it('gets a single contact by ID', function () {
    Http::fake([
        '*/contacts/cnt_abc' => Http::response([
            'id' => 'cnt_abc',
            'email' => 'get@example.com',
            'first_name' => 'Get',
            'last_name' => 'Me',
            'status' => 'complained',
            'subscribed_at' => null,
        ], 200),
    ]);

    $resource = new ContactResource(makeContactClient());
    $contact = $resource->get('cnt_abc');

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->id)->toBe('cnt_abc')
        ->and($contact->status)->toBe(ContactStatus::Complained);
});

it('throws VolpaMailException on 404 for contact get', function () {
    Http::fake([
        '*/contacts/missing' => Http::response(['message' => 'not_found'], 404),
    ]);

    $resource = new ContactResource(makeContactClient());
    $resource->get('missing');
})->throws(VolpaMailException::class, 'not_found');

it('Contact::fromArray tolerates missing optional fields', function () {
    $contact = Contact::fromArray([
        'id' => 'cnt_min',
        'email' => 'min@example.com',
        'status' => 'active',
    ]);

    expect($contact->firstName)->toBeNull()
        ->and($contact->lastName)->toBeNull()
        ->and($contact->tags)->toBe([])
        ->and($contact->attributes)->toBe([])
        ->and($contact->subscribedAt)->toBeNull()
        ->and($contact->createdAt)->toBeNull();
});

it('Contact::fromArray falls back to Active for unknown status', function () {
    $contact = Contact::fromArray([
        'id' => 'cnt_x',
        'email' => 'x@x.test',
        'status' => 'unknown_status',
    ]);

    expect($contact->status)->toBe(ContactStatus::Active);
});

it('sends X-API-Key header when creating contact', function () {
    Http::fake([
        '*/contacts' => Http::response(['id' => 'c1', 'email' => 'a@b.test', 'status' => 'active'], 201),
    ]);

    $resource = new ContactResource(makeContactClient());
    $resource->create(['email' => 'a@b.test']);

    Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'test-key'));
});
