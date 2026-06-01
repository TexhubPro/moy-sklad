<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\MoySklad\Config;
use TexHub\MoySklad\MoySklad as MoySkladClient;

class MoySkladServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/moy-sklad.php', 'moy-sklad');

        $this->app->singleton(Config::class, function ($app): Config {
            return Config::fromArray((array) $app['config']->get('moy-sklad', []));
        });

        $this->app->singleton(MoySkladClient::class, function ($app): MoySkladClient {
            return new MoySkladClient($app->make(Config::class));
        });

        $this->app->alias(MoySkladClient::class, 'moy-sklad');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/moy-sklad.php' => $this->app->configPath('moy-sklad.php'),
            ], 'moy-sklad-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Config::class, MoySkladClient::class, 'moy-sklad'];
    }
}
