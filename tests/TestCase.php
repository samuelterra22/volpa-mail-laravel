<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SamuelTerra\VolpaMail\VolpaMailServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            VolpaMailServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('volpa-mail.api_key', 'test-key');
        $app['config']->set('volpa-mail.base_url', 'https://api.mail.volpa.test/v1');
        $app['config']->set('volpa-mail.timeout', 5);
        $app['config']->set('volpa-mail.retry', ['times' => 1, 'sleep' => 0]);

        $app['config']->set('mail.default', 'volpa-mail');
        $app['config']->set('mail.mailers.volpa-mail', [
            'transport' => 'volpa-mail',
        ]);
        $app['config']->set('mail.from', ['address' => 'no-reply@volpa.test', 'name' => 'Volpa']);
    }
}
