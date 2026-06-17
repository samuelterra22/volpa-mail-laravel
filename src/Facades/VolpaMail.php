<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Facades;

use Illuminate\Support\Facades\Facade;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;

/**
 * Facade for the Volpa Mail SDK.
 *
 * Forwards static calls to the {@see VolpaMailClient} resolved from the
 * container. Typical usage: `VolpaMail::emails()->send([...])`.
 *
 * @method static EmailResource emails()
 * @method static array<string, mixed> post(string $uri, array<string, mixed> $payload)
 * @method static array<string, mixed> get(string $uri, array<string, mixed> $query = [])
 *
 * @see VolpaMailClient
 */
final class VolpaMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VolpaMailClient::class;
    }
}
