<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\IdUuidTrait;
use App\Enum\TransactionTypeEnum;
use App\Repository\TransactionRepository;
use App\State\TransactionStateProcessor;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['Transaction', 'Strict'])]
#[CustomAssert\Entity\Transaction\TransactionConstraint(groups: ['Strict'])]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ApiResource(
    operations: [
        // Better to use a DTO than the entity just because of fields type validation in payload
        new Post(security: 'is_granted("ROLE_TRANSACTION_CREATE")', processor: TransactionStateProcessor::class),
    ],
)]
class Transaction
{
    use IdUuidTrait;
    use TimestampableEntity;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank]
    #[CustomAssert\Entity\Transaction\Amount]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $amount;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank]
    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Wallet::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Wallet $walletFrom;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank]
    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Wallet::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Wallet $walletTo;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank(allowNull: true)]
    #[ORM\Column(type: 'text', nullable: true)]
    private string $externalIdentifier;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank]
    #[Assert\Type(TransactionTypeEnum::class)]
    #[ORM\Column(type: 'string', enumType: TransactionTypeEnum::class)]
    private TransactionTypeEnum $type;

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

    public function getExternalIdentifier(): ?string
    {
        return $this->externalIdentifier;
    }

    public function setExternalIdentifier(?string $externalIdentifier): Transaction
    {
        $this->externalIdentifier = $externalIdentifier;

        return $this;
    }

    public function getType(): ?TransactionTypeEnum
    {
        return $this->type;
    }

    public function setType(TransactionTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }
}
