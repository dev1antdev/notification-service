<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;

final readonly class ChannelSet
{
    /**
     * @var array<string, Channel>
     */
    private array $items;

    public function __construct(array $channels)
    {
        if ($channels === []) {
            throw InvariantViolation::because('ChannelSet must contain as least one channel.');
        }

        $map = [];

        foreach ($channels as $channel) {
            if (!$channel instanceof Channel) {
                throw InvariantViolation::because('ChannelSet must contain only Channel instances.');
            }

            $map[$channel->value] = $channel;
        }

        $this->items = $map;
    }

    public static function of(Channel ...$channels): self
    {
        return new self($channels);
    }

    public static function fromStrings(array $channels): self
    {
        if ($channels === []) {
            throw InvariantViolation::because('ChannelSet must contain at least one channel.');
        }

        $out = [];

        foreach ($channels as $channel) {
            $out[] = Channel::fromString($channel);
        }

        return new self($out);
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function contains(Channel $channel): bool
    {
        return isset($this->items[$channel->value]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toStrings(): array
    {
        return array_values(
            array_map(static fn(Channel $channel) => $channel->value, $this->items),
        );
    }
}
