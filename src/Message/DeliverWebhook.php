<?php

declare(strict_types=1);

namespace App\Message;

// One message per subscriber, so a failing app never replays the Discord notification or the other apps
final readonly class DeliverWebhook
{
    public function __construct(public string $transactionId, public string $subscriber)
    {
    }
}
