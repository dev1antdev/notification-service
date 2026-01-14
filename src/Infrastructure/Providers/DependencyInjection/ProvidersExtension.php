<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class ProvidersExtension extends Extension
{
    public function getAlias(): string
    {
        return 'providers';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('providers.config', $config);
    }
}
