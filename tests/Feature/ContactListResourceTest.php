<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\ContactListResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\ContactList;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeContactListClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('lists contact lists and returns array of ContactList DTOs', function () {
    Http::fake([
        '*/contact-lists' => Http::response([
            'data' => [
                [
                    'id' => 'lst_1',
                    'name' => 'Newsletter',
                    'slug' => 'newsletter',
                    'total_contacts' => 500,
                    'total_subscribed' => 450,
                ],
                [
                    'id' => 'lst_2',
                    'name' => 'Promos',
                    'slug' => 'promos',
                    'total_contacts' => 100,
                    'total_subscribed' => 90,
                ],
            ],
        ], 200),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $lists = $resource->list();

    expect($lists)->toHaveCount(2)
        ->and($lists[0])->toBeInstanceOf(ContactList::class)
        ->and($lists[0]->id)->toBe('lst_1')
        ->and($lists[0]->name)->toBe('Newsletter')
        ->and($lists[0]->slug)->toBe('newsletter')
        ->and($lists[0]->totalContacts)->toBe(500)
        ->and($lists[0]->totalSubscribed)->toBe(450)
        ->and($lists[1]->id)->toBe('lst_2');
});

it('creates a contact list and returns ContactList DTO', function () {
    Http::fake([
        '*/contact-lists' => Http::response([
            'id' => 'lst_new',
            'name' => 'VIPs',
            'slug' => 'vips',
            'total_contacts' => 0,
            'created_at' => '2026-06-20T00:00:00Z',
        ], 201),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $list = $resource->create([
        'name' => 'VIPs',
        'slug' => 'vips',
        'description' => 'VIP customers',
    ]);

    expect($list)->toBeInstanceOf(ContactList::class)
        ->and($list->id)->toBe('lst_new')
        ->and($list->name)->toBe('VIPs')
        ->and($list->slug)->toBe('vips')
        ->and($list->totalContacts)->toBe(0)
        ->and($list->createdAt)->toBe('2026-06-20T00:00:00Z');
});

it('gets a single contact list by ID', function () {
    Http::fake([
        '*/contact-lists/lst_abc' => Http::response([
            'id' => 'lst_abc',
            'name' => 'Subscribers',
            'slug' => 'subscribers',
            'description' => 'All subscribers',
            'total_contacts' => 1000,
            'total_subscribed' => 950,
            'created_at' => '2026-01-01T00:00:00Z',
        ], 200),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $list = $resource->get('lst_abc');

    expect($list)->toBeInstanceOf(ContactList::class)
        ->and($list->id)->toBe('lst_abc')
        ->and($list->description)->toBe('All subscribers')
        ->and($list->totalSubscribed)->toBe(950);
});

it('throws VolpaMailException on 404 for contact list get', function () {
    Http::fake([
        '*/contact-lists/missing' => Http::response(['message' => 'not_found'], 404),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $resource->get('missing');
})->throws(VolpaMailException::class, 'not_found');

it('imports contacts into a list and returns result', function () {
    Http::fake([
        '*/contact-lists/lst_1/import' => Http::response([
            'list_id' => 'lst_1',
            'imported' => 3,
        ], 202),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $result = $resource->import('lst_1', [
        ['email' => 'a@test.com', 'first_name' => 'A'],
        ['email' => 'b@test.com'],
        ['email' => 'c@test.com', 'tags' => ['tag1']],
    ]);

    expect($result)->toBe(['list_id' => 'lst_1', 'imported' => 3]);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'contact-lists/lst_1/import')
            && isset($body['contacts'])
            && count($body['contacts']) === 3
            && $body['contacts'][0]['email'] === 'a@test.com';
    });
});

it('sends X-API-Key header when listing contact lists', function () {
    Http::fake([
        '*/contact-lists' => Http::response(['data' => []], 200),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $resource->list();

    Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'test-key'));
});

it('ContactList::fromArray tolerates missing optional fields', function () {
    $list = ContactList::fromArray([
        'id' => 'lst_min',
        'name' => 'Minimal',
        'slug' => 'minimal',
    ]);

    expect($list->description)->toBeNull()
        ->and($list->totalContacts)->toBeNull()
        ->and($list->totalSubscribed)->toBeNull()
        ->and($list->createdAt)->toBeNull();
});

it('throws VolpaMailException on 404 for contact list import', function () {
    Http::fake([
        '*/contact-lists/bad/import' => Http::response(['message' => 'list not found'], 404),
    ]);

    $resource = new ContactListResource(makeContactListClient());
    $resource->import('bad', [['email' => 'x@y.test']]);
})->throws(VolpaMailException::class, 'list not found');
