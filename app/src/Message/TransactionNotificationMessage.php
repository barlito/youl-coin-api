<?php

declare(strict_types=1);

namespace App\Message;

class TransactionNotificationMessage{

    private string $amount;

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): TransactionNotificationMessage
    {
        $this->amount = $amount;
        return $this;
    }
}
