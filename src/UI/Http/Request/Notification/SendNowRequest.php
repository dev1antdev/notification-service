<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

use App\UI\Http\Exception\BadRequestHttpException;
use App\UI\Http\Exception\ValidationError;
use JsonException;
use Symfony\Component\Uid\Uuid;

final class SendNowRequest
{
    /** @var ValidationError[] */
    private array $errors = [];

    /**
     * @param string[] $channels
     * @param array<string, array<string, mixed>> $addresses
     * @param string[] $tags
     *
     * @throws JsonException
     */
    public function __construct(
        public readonly ?string $correlationId,
        public readonly Recipient $recipient,
        public readonly array $channels,
        public readonly ?array $addresses,
        public readonly Content $content,
        public readonly ?Options $options,
        public readonly ?array $tags,
    ) {
        if (empty($channels)) {
            $this->errors[] = new ValidationError('channels', 'empty', 'At least one channel must be provided.');
        }

        if (isset($addresses) && empty($addresses)) {
            $this->errors[] = new ValidationError('addresses', 'empty', 'At least one address must be provided.');
        }

        if (isset($tags) && empty($addresses)) {
            $this->errors[] = new ValidationError('tags', 'empty', 'At least one tag must be provided.');
        }

        if (!empty($this->errors)) {
            throw BadRequestHttpException::fromErrors($this->errors);
        }
    }
}
