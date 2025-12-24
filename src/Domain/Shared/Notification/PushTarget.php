<?php

declare(strict_types=1);

namespace App\Domain\Shared\Notification;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class PushTarget
{
    private const int MAX_DEVICE_TOKEN_LENGTH = 4096;
    private const int MAX_USER_ID_LENGTH = 255;

    private function __construct(
        private ?string $deviceToken,
        private ?string $userId,
    ) {}

    public static function forDeviceToken(string $deviceToken): self
    {
        $deviceToken = trim($deviceToken);

        if ($deviceToken === '' || mb_strlen($deviceToken) > self::MAX_DEVICE_TOKEN_LENGTH) {
            throw InvariantViolation::because('Push device token is invalid');
        }

        return new self($deviceToken, null);
    }

    public static function forUserId(string $userId): self
    {
        $userId = trim($userId);

        if ($userId === '' || mb_strlen($userId) > self::MAX_USER_ID_LENGTH) {
            throw InvariantViolation::because('Push user ID is invalid');
        }

        return new self(null, $userId);
    }

    public static function forDeviceAndUser(string $deviceToken, string $userId): self
    {
        return new self(self::forDeviceToken($deviceToken)->deviceToken, self::forUserId($userId)->userId);
    }

    public function hasDeviceToken(): bool
    {
        return $this->deviceToken !== null;
    }

    public function hasUserId(): bool
    {
        return $this->userId !== null;
    }

    public function deviceToken(): ?string
    {
        return $this->deviceToken;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }
}
