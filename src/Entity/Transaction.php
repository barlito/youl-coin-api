<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
#[ApiResource]
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    private ?string $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $amount;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Wallet $walletFrom;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Wallet $walletTo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $message;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAmount(): ?string
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

    public function setWalletFrom(Wallet $walletFrom): self
    {
        $this->walletFrom = $walletFrom;

        return $this;
    }

    public function getWalletTo(): ?Wallet
    {
        return $this->walletTo;
    }

    public function setWalletTo(Wallet $walletTo): self
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
}
