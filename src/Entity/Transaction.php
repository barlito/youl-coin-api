<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\TransactionTypeEnum;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
#[ApiResource(
    collectionOperations: ['get'],
    itemOperations: ['get'],
)]
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
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $amount;

    /**
    @ORM\ManyToOne(targetEntity=Wallet::class)
     */
    private ?Wallet $walletFrom;

    /**
    @ORM\ManyToOne(targetEntity=Wallet::class)
     */
    private ?Wallet $walletTo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $message;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(TransactionTypeEnum::VALUES)
     */
    private string $type;

    public function getId(): ?string
    {
        return $this->id;
    }

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
