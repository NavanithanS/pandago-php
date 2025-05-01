<?php
namespace Nava\Pandago\Laravel;

use Illuminate\Support\ServiceProvider;
use Nava\Pandago\Config;
use Nava\Pandago\Contracts\ClientInterface;
use Nava\Pandago\PandagoClient;

class PandagoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/pandago.php' => config_path('pandago.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/pandago.php', 'pandago');

        $this->app->singleton(Config::class, function ($app) {
            $config = $app['config']['pandago'];

            return Config::fromArray([
                'client_id'   => $config['client_id'],
                'key_id'      => $config['key_id'],
                'scope'       => $config['scope'],
                'private_key' => $config['private_key'],
                'country'     => $config['country'] ?? 'my',
                'environment' => $config['environment'] ?? 'sandbox',
                'timeout'     => $config['timeout'] ?? 30,
            ]);
        });

        $this->app->singleton(ClientInterface::class, function ($app) {
            return new PandagoClient(
                $app->make(Config::class),
                null,
                $app['log']
            );
        });

        $this->app->alias(ClientInterface::class, 'pandago');
    }
}
