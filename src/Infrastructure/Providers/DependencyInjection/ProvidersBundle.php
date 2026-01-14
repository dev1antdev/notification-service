<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ProvidersBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ProvidersExtension();
    }
}
