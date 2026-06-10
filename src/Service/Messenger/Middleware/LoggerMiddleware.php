<?php

declare(strict_types=1);

namespace App\Service\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class LoggerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger, private readonly SerializerInterface $serializer)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $context = [
            'class' => $envelope->getMessage()::class,
            'message' => $this->serializer->serialize($envelope->getMessage(), 'json'),
        ];
        // Call other middlewares if we need something from another middleware job
        $envelope = $stack->next()->handle($envelope, $stack);
        if ($envelope->last(ReceivedStamp::class) instanceof ReceivedStamp) {
            $this->logger->info('Received {class}', $context);
        }

        return $envelope;
    }
}
