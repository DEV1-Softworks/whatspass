<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Symfony\DependencyInjection;

use Dev1\Whatspass\Contracts\WhatspassServiceInterface;
use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use Dev1\Whatspass\WhatspassService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class WhatspassExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register WhatspassConfig
        $configDefinition = new Definition(WhatspassConfig::class);
        $configDefinition->setArguments([
            $config['phone_number_id'],
            $config['access_token'],
            $config['api_version'],
            $config['base_url'],
            $config['default_template_name'],
            $config['default_language_code'],
            $config['otp_length'],
            $config['otp_expiry'],
            $config['alphanumeric_otp'],
        ]);
        $container->setDefinition(WhatspassConfig::class, $configDefinition);

        // Register WhatspassClient
        $clientDefinition = new Definition(WhatspassClient::class);
        $clientDefinition->setArguments([
            new Reference(WhatspassConfig::class),
            null,
            null,
        ]);
        $container->setDefinition(WhatspassClient::class, $clientDefinition);

        // Register OtpGenerator
        $generatorDefinition = new Definition(OtpGenerator::class);
        $container->setDefinition(OtpGenerator::class, $generatorDefinition);

        // Register WhatspassService
        $serviceDefinition = new Definition(WhatspassService::class);
        $serviceDefinition->setArguments([
            new Reference(WhatspassConfig::class),
            new Reference(WhatspassClient::class),
            new Reference(OtpGenerator::class),
        ]);
        $serviceDefinition->setPublic(true);
        $container->setDefinition(WhatspassService::class, $serviceDefinition);
        $container->setAlias(WhatspassServiceInterface::class, WhatspassService::class)->setPublic(true);
        $container->setAlias('whatspass', WhatspassService::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'whatspass';
    }
}
