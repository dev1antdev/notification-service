<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DeliveryRoutingBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DeliveryRoutingExtension();
    }
}
