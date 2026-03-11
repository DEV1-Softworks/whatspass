<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Laravel;

use Dev1\Whatspass\Contracts\WhatspassServiceInterface;
use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use Dev1\Whatspass\WhatspassService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class WhatspassServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/whatspass.php',
            'whatspass',
        );

        $this->app->singleton(WhatspassConfig::class, function ($app) {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('whatspass', []);

            return WhatspassConfig::fromArray($config);
        });

        $this->app->singleton(WhatspassClient::class, function ($app) {
            return new WhatspassClient(
                config: $app->make(WhatspassConfig::class),
                httpClient: new Client(['timeout' => 30, 'connect_timeout' => 10]),
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->singleton(OtpGenerator::class, fn () => new OtpGenerator());

        $this->app->singleton(WhatspassService::class, function ($app) {
            return new WhatspassService(
                config: $app->make(WhatspassConfig::class),
                client: $app->make(WhatspassClient::class),
                generator: $app->make(OtpGenerator::class),
            );
        });

        $this->app->alias(WhatspassService::class, WhatspassServiceInterface::class);
        $this->app->alias(WhatspassService::class, 'whatspass');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/whatspass.php' => config_path('whatspass.php'),
            ], 'whatspass-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            WhatspassConfig::class,
            WhatspassClient::class,
            WhatspassService::class,
            WhatspassServiceInterface::class,
            OtpGenerator::class,
            'whatspass',
        ];
    }
}
