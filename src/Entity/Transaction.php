<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\IdUuidTrait;
use App\Enum\TransactionTypeEnum;
use App\Repository\TransactionRepository;
use App\State\TransactionStateProcessor;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['Transaction', 'Strict'])]
#[CustomAssert\Entity\Transaction\TransactionConstraint(groups: ['Strict'])]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['externalIdentifier' => 'exact'])]
#[ORM\UniqueConstraint(name: 'transaction_issuer_external_identifier_unique', columns: ['issuer_id', 'external_identifier'])]
#[ApiResource(
    operations: [
        // Reads are scoped to the transactions of the calling API client (IssuerScopedTransactionExtension)
        new Get(requirements: ['id' => Requirement::UUID], security: 'is_granted("ROLE_TRANSACTION_READ")'),
        new GetCollection(security: 'is_granted("ROLE_TRANSACTION_READ")'),
        // Better to use a DTO than the entity just because of fields type validation in payload
        // Validated by the handler, under the wallet locks and after the idempotent replay lookup
        new Post(
            security: 'is_granted("ROLE_TRANSACTION_BANK_TO_USER") or is_granted("ROLE_TRANSACTION_USER_TO_BANK") or is_granted("ROLE_TRANSACTION_USER_TO_USER")',
            // The required role depends on the wallets, only known once the payload is denormalized
            securityPostDenormalize: 'is_granted("TRANSACTION_CREATE", object)',
            securityPostDenormalizeMessage: 'This API key cannot make this transaction, or the X-Player-Token header does not belong to the owner of walletFrom.',
            validate: false,
            processor: TransactionStateProcessor::class,
        ),
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

    // Blank/type checks live in TransactionConstraintValidator: required or forbidden depends on the transaction type (Mint has no walletFrom, Burn no walletTo)
    #[Groups('transaction:notification')]
    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Wallet::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Wallet $walletFrom = null;

    #[Groups('transaction:notification')]
    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Wallet::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Wallet $walletTo = null;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank(allowNull: true)]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $externalIdentifier = null;

    // API client that created the transaction: scopes the externalIdentifier idempotency key
    #[Ignore]
    #[ORM\ManyToOne(targetEntity: ApiUser::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ApiUser $issuer = null;

    #[Groups('transaction:notification')]
    #[Assert\NotBlank]
    #[Assert\Type(TransactionTypeEnum::class)]
    #[ORM\Column(type: 'string', enumType: TransactionTypeEnum::class)]
    private TransactionTypeEnum $type;

    // Required for Mint/Burn (checked in TransactionConstraintValidator), never writable through the API
    #[Ignore]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    // The admin who triggered a Mint/Burn from the bank wallet page, never writable through the API
    #[Ignore]
    #[ORM\ManyToOne(targetEntity: DiscordUser::class)]
    #[ORM\JoinColumn(referencedColumnName: 'discord_id', nullable: true)]
    private ?DiscordUser $initiatedBy = null;

    public function getAmount(): ?string
    {
        // Unset until denormalized: a payload missing the field must reach validation, not crash
        return $this->amount ?? null;
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

    public function getExternalIdentifier(): ?string
    {
        return $this->externalIdentifier;
    }

    public function setExternalIdentifier(?string $externalIdentifier): Transaction
    {
        $this->externalIdentifier = $externalIdentifier;

        return $this;
    }

    public function getIssuer(): ?ApiUser
    {
        return $this->issuer;
    }

    public function setIssuer(?ApiUser $issuer): self
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getType(): ?TransactionTypeEnum
    {
        return $this->type ?? null;
    }

    public function setType(TransactionTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getInitiatedBy(): ?DiscordUser
    {
        return $this->initiatedBy;
    }

    public function setInitiatedBy(?DiscordUser $initiatedBy): self
    {
        $this->initiatedBy = $initiatedBy;

        return $this;
    }
}
