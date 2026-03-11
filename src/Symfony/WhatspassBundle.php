<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Symfony;

use Dev1\Whatspass\Symfony\DependencyInjection\WhatspassExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WhatspassBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new WhatspassExtension();
    }
}
