<?php

declare(strict_types=1);

namespace App\Application\Common\Mapper;

use App\Application\Ports\Template\TemplateRenderer;
use App\Domain\Delivery\ValueObject\Content\DeliveryContent;
use App\Domain\Delivery\ValueObject\Content\SnapshotContent;
use App\Domain\Delivery\ValueObject\Content\TemplateRefContent;
use App\Domain\Notification\ValueObject\InlineContent;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\TemplateContent;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\ValueObject\JsonObject;
use App\Domain\Template\ValueObject\RenderedMessage;

final readonly class ContentFactory
{
    public const string MODE_SNAPSHOT = 'snapshot';
    public const string MODE_TEMPLATE_REF = 'template_ref';

    public function __construct(
        private TemplateRenderer $templateRenderer,
    ) {}

    public function build(
        Channel $channel,
        NotificationContent $content,
        string $mode = self::MODE_SNAPSHOT
    ): DeliveryContent {
        if ($content instanceof InlineContent) {
            return new SnapshotContent($channel, new JsonObject($this->buildPayload($content)));
        }

        if ($content instanceof TemplateContent) {
            if ($mode === self::MODE_TEMPLATE_REF) {
                return new TemplateRefContent($channel, $content->templateRef(), $content->variables());
            }

            $rendered = $this->templateRenderer->render($content->templateRef(), $content->variables());

            if (!$rendered->channel()->equals($channel)) {
                throw InvariantViolation::because('Rendered message channel does not match channel of notification.');
            }

            return new SnapshotContent($channel, new JsonObject($this->buildPayload($rendered)));
        }

        throw InvariantViolation::because('Unknown notification content type.');
    }

    public function buildPayload(InlineContent|RenderedMessage $content): array
    {
        return [
            'subject' => $content->subject(),
            'text' => $content->text(),
            'html' => $content->html(),
            'pushTitle' => $content->pushTitle(),
            'pushBody' => $content->pushBody(),
            'pushData' => $content->pushData(),
        ];
    }
}
