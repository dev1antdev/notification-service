<?php

declare(strict_types=1);

namespace App\Domain\Shared\Notification;

enum BuiltInChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case WEBHOOK = 'webhook';
    case SLACK = 'slack';
    case TELEGRAM = 'telegram';
    case WHATSAPP = 'whatsapp';
}
