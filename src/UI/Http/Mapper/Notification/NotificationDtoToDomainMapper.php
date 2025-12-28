<?php

declare(strict_types=1);

namespace App\UI\Http\Mapper\Notification;

use App\Domain\Notification\ValueObject\ChannelSet;
use App\Domain\Notification\ValueObject\InlineContent;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Notification\ValueObject\Tags;
use App\Domain\Notification\ValueObject\TemplateContent;
use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Shared\Notification\PushTarget;
use App\Domain\Shared\ValueObject\JsonObject;
use App\UI\Http\Exception\BadRequestHttpException;
use App\UI\Http\Exception\ValidationError;
use App\UI\Http\Request\Notification\Content;
use App\UI\Http\Request\Notification\PushTarget as UIPushTarget;
use App\UI\Http\Request\Notification\Recipient as UIRecipient;
use JsonException;

final readonly class NotificationDtoToDomainMapper
{
    /**
     * @throws JsonException
     */
    public function recipient(UIRecipient $recipient): Recipient
    {
        return new Recipient(
            email: $recipient->email,
            phoneNumber: $recipient->phone,
            pushTarget: $this->buildPushTarget($recipient->pushTarget),
        );
    }

    public function channels(array $channels): ChannelSet
    {
        return ChannelSet::fromStrings($channels);
    }

    /**
     * @throws JsonException
     */
    public function content(Content $content): NotificationContent
    {
        if ($content->type === 'template') {
            $templateRef = TemplateRef::create(
                $content->templateRef->templateId,
                $content->templateRef->version,
                $content->templateRef->locale,
            );

            return new TemplateContent(
                templateRef: $templateRef,
                variables: new Variables($content->variables),
            );
        }

        if ($content->type === 'inline') {
            $email = $content->payloads['email'] ?? null;
            $sms = $content->payloads['sms'] ?? null;
            $push = $content->payloads['push'] ?? null;
            $subject = $email?->subject;

            $html = null;
            $text = null;

            if ($email) {
                $format = mb_strtolower($email->format ?? 'text');

                if ($format === 'html') {
                    $html = $email->body;
                } else {
                    $text = $email->body;
                }
            }

            $smsText = $sms?->body;

            $pushTitle = null;
            $pushBody = null;
            $pushData = [];

            if ($push) {
                if (is_array($push->data)) {
                    $pushTitle = $push->data['title'] ?? null;
                    $pushBody = $push->data['body'] ?? null;
                    $pushData = $push->data['data'] ?? [];
                } else {
                    $pushBody = $push->body;
                }
            }

            return new InlineContent(
                subject: $subject,
                text: $text ?? $smsText,
                html: $html,
                pushTitle: $pushTitle,
                pushBody: $pushBody,
                pushData: $pushData,
            );
        }

        throw BadRequestHttpException::fromError(
            new ValidationError('content', 'invalid_format', 'Content type is invalid.')
        );
    }

    public function tags(?array $tags): Tags
    {
        if ($tags === null) {
            return Tags::empty();
        }

        return new Tags($tags);
    }

    /**
     * @param array<string, mixed> $addresses
     *
     * @return array<string, JsonObject>
     */
    public function addresses(?array $addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        return array_map(static fn (array $address) => new JsonObject($address), $addresses);
    }

    /**
     * @throws JsonException
     */
    private function buildPushTarget(?UIPushTarget $pushTarget): ?PushTarget
    {
        if ($pushTarget === null) {
            return null;
        }

        if ($pushTarget->userId && $pushTarget->deviceToken) {
            return PushTarget::forDeviceAndUser($pushTarget->deviceToken, $pushTarget->userId);
        }

        if ($pushTarget->userId) {
            return PushTarget::forUserId($pushTarget->userId);
        }

        if ($pushTarget->deviceToken) {
            return PushTarget::forDeviceToken($pushTarget->deviceToken);
        }

        throw BadRequestHttpException::fromError(
            new ValidationError('recipient', 'invalid_format', 'Push target is invalid.')
        );
    }
}
