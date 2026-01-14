<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\DependencyInjection;

use App\Domain\Delivery\Policy\RoutingPolicy;
use App\Infrastructure\Routing\ConfigRoutingPolicy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class DeliveryRoutingExtension extends Extension
{
    public function getAlias(): string
    {
        return 'delivery_routing';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('delivery_routing.config', $config);

        $container->register(ConfigRoutingPolicy::class)
            ->setArgument('$config', $config);

        $container->setAlias(RoutingPolicy::class, ConfigRoutingPolicy::class)
            ->setPublic(false);
    }
}
