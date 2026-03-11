<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('whatspass');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('phone_number_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The Meta WhatsApp Phone Number ID.')
                ->end()
                ->scalarNode('access_token')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The Meta access token for the WhatsApp Business API.')
                ->end()
                ->scalarNode('api_version')
                    ->defaultValue('v19.0')
                    ->info('The Meta Graph API version.')
                ->end()
                ->scalarNode('base_url')
                    ->defaultValue('https://graph.facebook.com')
                    ->info('The Meta Graph API base URL.')
                ->end()
                ->scalarNode('default_template_name')
                    ->defaultValue('otp_authentication')
                    ->info('The name of the pre-approved WhatsApp OTP template.')
                ->end()
                ->scalarNode('default_language_code')
                    ->defaultValue('en_US')
                    ->info('BCP-47 language code for the OTP template.')
                ->end()
                ->integerNode('otp_length')
                    ->defaultValue(6)
                    ->min(4)
                    ->max(12)
                    ->info('Number of characters in the generated OTP (4–12).')
                ->end()
                ->integerNode('otp_expiry')
                    ->defaultValue(300)
                    ->min(60)
                    ->info('OTP validity in seconds (minimum 60).')
                ->end()
                ->booleanNode('alphanumeric_otp')
                    ->defaultFalse()
                    ->info('Generate alphanumeric OTPs instead of numeric-only.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
