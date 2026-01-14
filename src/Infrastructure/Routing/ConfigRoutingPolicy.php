<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Domain\Delivery\Policy\RoutingPolicy;
use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Delivery\ValueObject\Route;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\ValueObject\JsonObject;

final readonly class ConfigRoutingPolicy implements RoutingPolicy
{
    public function __construct(private array $config)
    {
    }

    public function chooseProvider(Channel $channel, Address $address): Route
    {
        $channelName = $channel->name();
        $config = $this->config['channels'][$channelName] ?? null;

        if ($config === null) {
            throw InvariantViolation::because('No routing configuration for channel ' . $channelName);
        }

        $provider = ProviderName::fromString($config['primary']);
        $options = isset($config['options']) && is_array($config['options'])
            ? new JsonObject($config['options'])
            : null;

        return new Route($provider, $options);
    }
}
