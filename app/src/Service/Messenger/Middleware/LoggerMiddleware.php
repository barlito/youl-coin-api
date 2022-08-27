<?php

declare(strict_types=1);

namespace App\Service\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class LoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $messengerAuditLogger,
        private readonly SerializerInterface $serializer,
    ) {
        $this->logger = $messengerAuditLogger;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        //todo create a class on barlito/utils and move this
        $serializerContext = (new ObjectNormalizerContextBuilder())
            ->withGroups(['default', 'test'])
            ->toArray()
        ;

        $context = [
            'class' => \get_class($envelope->getMessage()),
            'message' => $this->serializer->serialize($envelope->getMessage(), 'json', $serializerContext),
        ];
        // Call other middlewares if we need something from another middleware job
        $envelope = $stack->next()->handle($envelope, $stack);
        if ($envelope->last(ReceivedStamp::class)) {
            $this->logger->info('Received {class}', $context);
        }

        return $envelope;
    }
}
