<?php

declare(strict_types=1);

namespace App\DTO\BankWallet;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\BankWalletTransactionTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

// todo custom validator to validate is the wallet or discord user is set
class BankWalletTransactionDTO
{
    #[Assert\NotBlank]
    private int $amount;

    #[Assert\NotBlank]
    #[Assert\Type(type: BankWalletTransactionTypeEnum::class)]
    private BankWalletTransactionTypeEnum $bankWalletTransactionType;

    #[Assert\NotBlank]
    private int $transactionType;

    private ?DiscordUser $discordUser = null;

    private ?Wallet $wallet = null;

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

    public function getBankWalletTransactionType(): BankWalletTransactionTypeEnum
    {
        return $this->bankWalletTransactionType;
    }

    public function setBankWalletTransactionType(BankWalletTransactionTypeEnum $bankWalletTransactionType): BankWalletTransactionDTO
    {
        $this->bankWalletTransactionType = $bankWalletTransactionType;

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

    public function getDiscordUser(): ?DiscordUser
    {
        return $this->discordUser;
    }

    public function setDiscordUser(?DiscordUser $discordUser): BankWalletTransactionDTO
    {
        $this->discordUser = $discordUser;

        return $this;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): BankWalletTransactionDTO
    {
        $this->wallet = $wallet;

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
