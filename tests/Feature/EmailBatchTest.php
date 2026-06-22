<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\BatchResult;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeBatchClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('sends a batch and returns a BatchResult DTO', function () {
    Http::fake([
        '*/emails/batch' => Http::response([
            'batch_id' => 'bat_x',
            'total_queued' => 2,
            'status' => 'queued',
            'created_at' => '2026-06-22T10:00:00+00:00',
        ], 202),
    ]);

    $emails = [
        [
            'to' => [['email' => 'alice@example.com', 'name' => 'Alice']],
            'subject' => 'Hello Alice',
            'html' => '<p>Hi</p>',
        ],
        [
            'to' => [['email' => 'bob@example.com']],
            'subject' => 'Hello Bob',
            'html' => '<p>Hi</p>',
        ],
    ];

    $result = (new EmailResource(makeBatchClient()))->sendBatch($emails, ['message_stream' => 'broadcast']);

    expect($result)->toBeInstanceOf(BatchResult::class)
        ->and($result->batchId)->toBe('bat_x')
        ->and($result->totalQueued)->toBe(2)
        ->and($result->status)->toBe('queued')
        ->and($result->createdAt)->toBe('2026-06-22T10:00:00+00:00');

    Http::assertSent(function ($request) {
        if (! str_ends_with(rtrim($request->url(), '/'), '/emails/batch')) {
            return false;
        }

        if ($request->method() !== 'POST') {
            return false;
        }

        $body = $request->data();

        return isset($body['emails'])
            && count($body['emails']) === 2
            && ($body['message_stream'] ?? null) === 'broadcast';
    });
});

it('throws VolpaMailException on a 422 response', function () {
    Http::fake([
        '*/emails/batch' => Http::response([
            'message' => 'Validation failed: emails.0.to is required',
        ], 422),
    ]);

    (new EmailResource(makeBatchClient()))->sendBatch([
        ['subject' => 'Missing to field'],
    ]);
})->throws(VolpaMailException::class);
