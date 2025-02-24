<?php

declare(strict_types=1);

namespace App\Tests\Behat\Mock;

use App\Entity\Transaction;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionNotifierMock implements TransactionNotifierInterface
{
    #[ArrayShape([
        'transaction' => 'Transaction',
    ])]
    private static array $notifications = [];

    #[ArrayShape([
        'errorMessage' => 'string',
        'messageContent' => 'string',
    ])]
    private static array $errorNotifications = [];

    public function __construct(private TransactionNotifierInterface $discordNotifier)
    {
    }

    public function notifyNewTransaction(Transaction $transaction): void
    {
        $this->addNotification($transaction);
        $this->discordNotifier->notifyNewTransaction($transaction);
    }

    public function notifyErrorOnTransaction(string $errorMessage, string $messageContent): void
    {
        $this->addErrorNotification($errorMessage, $messageContent);
        $this->discordNotifier->notifyErrorOnTransaction($errorMessage, $messageContent);
    }

    public function countNotifications(): int
    {
        return \count(self::$notifications);
    }

    public function countErrorNotifications(): int
    {
        return \count(self::$errorNotifications);
    }

    public function reset(): void
    {
        self::$notifications = [];
        self::$errorNotifications = [];
    }

    private function addNotification(?Transaction $transaction = null): void
    {
        self::$notifications[] = [
            'transaction' => $transaction,
        ];
    }

    private function addErrorNotification(string $errorMessage = '', string $messageContent = ''): void
    {
        self::$errorNotifications[] = [
            'errorMessage' => $errorMessage,
            'messageContent' => $messageContent,
        ];
    }
}
