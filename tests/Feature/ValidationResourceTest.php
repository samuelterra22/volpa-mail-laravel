<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\ValidationResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\BulkValidationResult;
use SamuelTerra\VolpaMail\Data\EmailValidationResult;
use SamuelTerra\VolpaMail\Enums\ValidationReason;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeValidationClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('validates a valid email and returns EmailValidationResult DTO', function () {
    Http::fake([
        '*/validate/email' => Http::response([
            'email' => 'alice@example.com',
            'valid' => true,
            'reason' => null,
            'checks' => [
                'format' => true,
                'mx' => true,
                'disposable' => false,
                'suppressed' => false,
            ],
        ], 200),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $result = $resource->validate('alice@example.com');

    expect($result)->toBeInstanceOf(EmailValidationResult::class)
        ->and($result->email)->toBe('alice@example.com')
        ->and($result->valid)->toBeTrue()
        ->and($result->reason)->toBeNull()
        ->and($result->checks)->toBe([
            'format' => true,
            'mx' => true,
            'disposable' => false,
            'suppressed' => false,
        ]);
});

it('validates an invalid disposable email and maps reason to ValidationReason enum', function () {
    Http::fake([
        '*/validate/email' => Http::response([
            'email' => 'throwaway@mailinator.com',
            'valid' => false,
            'reason' => 'disposable',
            'checks' => [
                'format' => true,
                'mx' => true,
                'disposable' => true,
                'suppressed' => false,
            ],
        ], 200),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $result = $resource->validate('throwaway@mailinator.com');

    expect($result)->toBeInstanceOf(EmailValidationResult::class)
        ->and($result->valid)->toBeFalse()
        ->and($result->reason)->toBe(ValidationReason::Disposable);
});

it('sends POST to validate/email with the correct body and API key header', function () {
    Http::fake([
        '*/validate/email' => Http::response([
            'email' => 'test@example.com',
            'valid' => true,
            'reason' => null,
            'checks' => ['format' => true, 'mx' => true, 'disposable' => false, 'suppressed' => false],
        ], 200),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $resource->validate('test@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'validate/email')
            && $request->method() === 'POST'
            && $request->data()['email'] === 'test@example.com'
            && $request->hasHeader('X-API-Key', 'test-key');
    });
});

it('validates bulk emails and returns BulkValidationResult DTO', function () {
    Http::fake([
        '*/validate/bulk' => Http::response([
            'total' => 3,
            'valid' => 2,
            'data' => [
                [
                    'email' => 'ok@example.com',
                    'valid' => true,
                    'reason' => null,
                    'checks' => ['format' => true, 'mx' => true, 'disposable' => false, 'suppressed' => false],
                ],
                [
                    'email' => 'bad@mailinator.com',
                    'valid' => false,
                    'reason' => 'disposable',
                    'checks' => ['format' => true, 'mx' => true, 'disposable' => true, 'suppressed' => false],
                ],
                [
                    'email' => 'nosuchhost@nxdomain.invalid',
                    'valid' => false,
                    'reason' => 'no_mx',
                    'checks' => ['format' => true, 'mx' => false, 'disposable' => false, 'suppressed' => false],
                ],
            ],
        ], 200),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $result = $resource->validateBulk(['ok@example.com', 'bad@mailinator.com', 'nosuchhost@nxdomain.invalid']);

    expect($result)->toBeInstanceOf(BulkValidationResult::class)
        ->and($result->total)->toBe(3)
        ->and($result->valid)->toBe(2)
        ->and($result->data)->toHaveCount(3)
        ->and($result->data[0])->toBeInstanceOf(EmailValidationResult::class)
        ->and($result->data[0]->valid)->toBeTrue()
        ->and($result->data[0]->reason)->toBeNull()
        ->and($result->data[1]->valid)->toBeFalse()
        ->and($result->data[1]->reason)->toBe(ValidationReason::Disposable)
        ->and($result->data[2]->reason)->toBe(ValidationReason::NoMx);
});

it('sends POST to validate/bulk with the correct body', function () {
    Http::fake([
        '*/validate/bulk' => Http::response([
            'total' => 1,
            'valid' => 1,
            'data' => [
                [
                    'email' => 'a@example.com',
                    'valid' => true,
                    'reason' => null,
                    'checks' => ['format' => true, 'mx' => true, 'disposable' => false, 'suppressed' => false],
                ],
            ],
        ], 200),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $resource->validateBulk(['a@example.com']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'validate/bulk')
            && $request->method() === 'POST'
            && $request->data()['emails'] === ['a@example.com'];
    });
});

it('throws VolpaMailException on 422 for validate single', function () {
    Http::fake([
        '*/validate/email' => Http::response(['message' => 'unprocessable_entity'], 422),
    ]);

    $resource = new ValidationResource(makeValidationClient());
    $resource->validate('not-an-email');
})->throws(VolpaMailException::class);
