<?php

declare(strict_types=1);

namespace App\Service\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * @SuppressWarnings(PHPMD)
 */
class LoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $messengerAuditLogger)
    {
        $this->logger = $messengerAuditLogger;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $context = [
            'class' => \get_class($envelope->getMessage()),
        ];
        $envelope = $stack->next()->handle($envelope, $stack);
        if ($envelope->last(ReceivedStamp::class)) {
            $this->logger->info('Received {class}', $context);
        }

        return $envelope;
    }
}
