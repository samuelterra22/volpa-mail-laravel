<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Transport\VolpaMailTransport;

final class VolpaMailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/volpa-mail.php', 'volpa-mail');

        $this->app->singleton(VolpaMailClient::class, static function (Container $app): VolpaMailClient {
            /** @var Repository $configRepo */
            $configRepo = $app->make('config');
            /** @var array<string, mixed> $config */
            $config = $configRepo->get('volpa-mail');

            return new VolpaMailClient(
                http: $app->make(HttpFactory::class),
                apiKey: (string) ($config['api_key'] ?? ''),
                baseUrl: (string) $config['base_url'],
                timeout: (int) $config['timeout'],
                retry: $config['retry'],
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/volpa-mail.php' => $this->app->configPath('volpa-mail.php'),
        ], 'volpa-mail-config');

        Mail::extend('volpa-mail', function (): VolpaMailTransport {
            return new VolpaMailTransport(
                $this->app->make(VolpaMailClient::class)
            );
        });
    }
}
