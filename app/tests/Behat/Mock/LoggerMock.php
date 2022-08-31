<?php

declare(strict_types=1);

namespace App\Tests\Behat\Mock;

use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoggerMock implements LoggerInterface
{
    #[ArrayShape([
        'message' => 'string | Stringable',
        'context' => 'array',
        'level' => 'string | Stringable',
    ])]
    private static array $loggedMessages = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function error(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'error');
        $this->logger->error($message, $context);
    }

    public function getLoggedMessages(): array
    {
        return self::$loggedMessages;
    }

    public function getLoggedMessage(string $message): ?array
    {
        foreach (self::$loggedMessages as $loggedMessage) {
            if ($message === $loggedMessage['message']) {
                return $loggedMessage;
            }
        }

        return null;
    }

    public function containsLoggedMessage(string $message): ?array
    {
        foreach (self::$loggedMessages as $loggedMessage) {
            if (str_contains($loggedMessage['message'], $message)) {
                return $loggedMessage;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function emergency(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'emergency');
        $this->logger->emergency($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'alert');
        $this->logger->alert($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'critical');
        $this->logger->critical($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'warning');
        $this->logger->warning($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'notice');
        $this->logger->notice($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'info');
        $this->logger->info($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug(string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, 'debug');
        $this->logger->debug($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, string | Stringable $message, array $context = []): void
    {
        $this->addLoggedMessage($message, $context, $level);
        $this->logger->log($level, $message, $context);
    }

    public function reset(): void
    {
        self::$loggedMessages = [];
    }

    private function addLoggedMessage(string | Stringable $message, array $context, string $level): void
    {
        self::$loggedMessages[] = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
        ];
    }
}
