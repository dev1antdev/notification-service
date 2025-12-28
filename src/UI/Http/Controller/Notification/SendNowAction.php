<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Notification;

use App\Application\Notification\SendNow\SendNowHandler;
use App\UI\Http\Request\Notification\SendNowRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SendNowAction
{
    #[Route('v1/notifications/send-now', name: 'api.v1.notifications.send_now', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SendNowRequest $request): Response
    {
        var_dump($request);
        die;
    }
}
