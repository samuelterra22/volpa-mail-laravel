<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Facades;

use Illuminate\Support\Facades\Facade;
use SamuelTerra\VolpaMail\Client\Resources\BroadcastResource;
use SamuelTerra\VolpaMail\Client\Resources\ContactListResource;
use SamuelTerra\VolpaMail\Client\Resources\ContactResource;
use SamuelTerra\VolpaMail\Client\Resources\EmailResource;
use SamuelTerra\VolpaMail\Client\Resources\LgpdResource;
use SamuelTerra\VolpaMail\Client\Resources\MemberResource;
use SamuelTerra\VolpaMail\Client\Resources\SuppressionResource;
use SamuelTerra\VolpaMail\Client\Resources\ValidationResource;
use SamuelTerra\VolpaMail\Client\Resources\WebhookResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;

/**
 * Facade for the Volpa Mail SDK.
 *
 * Forwards static calls to the {@see VolpaMailClient} resolved from the
 * container. Typical usage: `VolpaMail::emails()->send([...])`.
 *
 * @method static EmailResource emails()
 * @method static SuppressionResource suppressions()
 * @method static ContactResource contacts()
 * @method static ContactListResource contactLists()
 * @method static BroadcastResource broadcasts()
 * @method static WebhookResource webhooks()
 * @method static ValidationResource validation()
 * @method static LgpdResource lgpd()
 * @method static MemberResource members()
 * @method static array<string, mixed> post(string $uri, array<string, mixed> $payload)
 * @method static array<string, mixed> patch(string $uri, array<string, mixed> $payload)
 * @method static array<string, mixed> get(string $uri, array<string, mixed> $query = [])
 * @method static array<string, mixed> delete(string $uri)
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
