<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Facades;

use Illuminate\Support\Facades\Facade;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;

/**
 * @method static EmailResource emails()
 * @method static array post(string $uri, array $payload)
 * @method static array get(string $uri, array $query = [])
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
