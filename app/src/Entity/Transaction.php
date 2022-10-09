<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\IdUuidTrait;
use App\Enum\TransactionTypeEnum;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    use IdUuidTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $amount;

    /**
     * @ORM\ManyToOne(targetEntity=Wallet::class)
     */
    private ?Wallet $walletFrom;

    /**
     * @ORM\ManyToOne(targetEntity=Wallet::class)
     */
    private ?Wallet $walletTo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $message;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(TransactionTypeEnum::VALUES)
     */
    private string $type;

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getWalletFrom(): ?Wallet
    {
        return $this->walletFrom;
    }

    public function setWalletFrom(?Wallet $walletFrom): self
    {
        $this->walletFrom = $walletFrom;

        return $this;
    }

    public function getWalletTo(): ?Wallet
    {
        return $this->walletTo;
    }

    public function setWalletTo(?Wallet $walletTo): self
    {
        $this->walletTo = $walletTo;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
