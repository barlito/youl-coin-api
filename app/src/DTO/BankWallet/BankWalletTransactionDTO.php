<?php

declare(strict_types=1);

namespace App\DTO\BankWallet;

use App\Entity\Wallet;
use App\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[CustomAssert\DTO\BankWallet]
class BankWalletTransactionDTO
{
    #[Assert\NotBlank]
    private int $amount;

    #[Assert\NotBlank]
    private int $transactionType;

    #[Assert\NotBlank]
    #[Assert\Type(type: Wallet::class)]
    private Wallet $walletFrom;

    #[Assert\NotBlank]
    #[Assert\Type(type: Wallet::class)]
    private Wallet $walletTo;

    private ?string $transactionNotes = null;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): BankWalletTransactionDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTransactionType(): int
    {
        return $this->transactionType;
    }

    public function setTransactionType(int $transactionType): BankWalletTransactionDTO
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    public function getWalletFrom(): ?Wallet
    {
        return $this->walletFrom;
    }

    public function setWalletFrom(?Wallet $walletFrom): BankWalletTransactionDTO
    {
        $this->walletFrom = $walletFrom;

        return $this;
    }

    public function getWalletTo(): ?Wallet
    {
        return $this->walletTo;
    }

    public function setWalletTo(?Wallet $walletTo): BankWalletTransactionDTO
    {
        $this->walletTo = $walletTo;

        return $this;
    }

    public function getTransactionNotes(): ?string
    {
        return $this->transactionNotes;
    }

    public function setTransactionNotes(?string $transactionNotes): BankWalletTransactionDTO
    {
        $this->transactionNotes = $transactionNotes;

        return $this;
    }
}
