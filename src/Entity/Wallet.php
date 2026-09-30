<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Symfony\Action\NotFoundAction;
use App\Entity\Traits\IdUlidTrait;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use App\State\BankWalletProvider;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        // Unusable get endpoint, only here to help serializer to generate the graph
        new Get(
            controller: NotFoundAction::class,
            openapi: false,
            output: false,
            read: false,
        ),
        new Get(
            uriTemplate: '/user/{discord_user_id}/wallet.{_format}',
            uriVariables: [
                'discord_user_id' => new Link(
                    fromProperty: 'wallet',
                    fromClass: DiscordUser::class,
                ),
            ],
            // 'default' exposes the ULID id, needed to build /api/wallets/{id} IRIs
            normalizationContext: ['groups' => ['wallet:read', 'default']],
            security: 'is_granted("ROLE_WALLET_READ")',
            name: 'wallet_by_discord_user',
        ),
        new Get(
            uriTemplate: '/bank/wallet.{_format}',
            normalizationContext: ['groups' => ['wallet:read', 'default']],
            security: 'is_granted("ROLE_WALLET_READ")',
            name: 'bank_wallet',
            provider: BankWalletProvider::class,
        )],
)]
#[ORM\UniqueConstraint(name: 'wallet_unique_bank_type', fields: ['type'], options: ['where' => "((type)::text = '" . WalletTypeEnum::BANK->value . "'::text)"])]
#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[CustomAssert\Entity\Wallet\WalletType(groups: ['wallet:create'])]
#[UniqueEntity(fields: ['discordUser'])]
class Wallet implements \Stringable
{
    use IdUlidTrait;
    use TimestampableEntity;

    // Only ever moved by a Transaction (TransactionHandler); a wallet created from the admin starts at 0
    #[Groups(['transaction:notification', 'wallet:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $amount = '0';

    // Not in 'wallet:read': exposed flat through getDiscordId()
    #[Groups('transaction:notification')]
    #[ORM\OneToOne(targetEntity: DiscordUser::class, inversedBy: 'wallet')]
    #[ORM\JoinColumn(referencedColumnName: 'discord_id')]
    private ?DiscordUser $discordUser = null;

    #[Assert\Type(WalletTypeEnum::class)]
    #[Groups(['transaction:notification', 'wallet:read'])]
    #[ORM\Column(type: 'string', enumType: WalletTypeEnum::class)]
    private WalletTypeEnum $type;

    #[Groups(['transaction:notification', 'wallet:read'])]
    #[ORM\Column(type: 'string')]
    private string $name;

    public function __toString(): string
    {
        return $this->getName();
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

    public function getDiscordUser(): ?DiscordUser
    {
        return $this->discordUser;
    }

    public function setDiscordUser(?DiscordUser $discordUser): self
    {
        $this->discordUser = $discordUser;

        return $this;
    }

    // Flat, minimal owner reference for 'wallet:read'; null for the bank wallet
    #[Groups('wallet:read')]
    public function getDiscordId(): ?string
    {
        return $this->discordUser?->getDiscordId();
    }

    public function getType(): WalletTypeEnum
    {
        return $this->type;
    }

    public function setType(WalletTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
