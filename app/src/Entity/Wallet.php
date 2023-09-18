<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Entity\Traits\IdUlidTrait;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    uriTemplate: '/user/{discord_user_id}/wallet.{_format}',
    operations: [new Get()],
    uriVariables: [
        'discord_user_id' => new Link(
            fromProperty: 'wallet',
            fromClass: DiscordUser::class,
        ),
    ],
)]
#[ORM\UniqueConstraint(name: 'wallet_unique_bank_type', fields: ['type'], options: ['where' => 'type = \'' . WalletTypeEnum::BANK . '\''])]
#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    use IdUlidTrait;
    use TimestampableEntity;

    #[Groups('transaction:notification')]
    #[ORM\Column(type: 'string', length: 255)]
    private string $amount;

    #[Groups('transaction:notification')]
    #[ORM\OneToOne(targetEntity: DiscordUser::class, inversedBy: 'wallet')]
    #[ORM\JoinColumn(referencedColumnName: 'discord_id')]
    private ?DiscordUser $discordUser = null;

    /**
     * @Assert\Choice(WalletTypeEnum::VALUES)
     */
    #[CustomAssert\Entity\Wallet\WalletType]
    #[Groups('transaction:notification')]
    #[ORM\Column(type: 'string')]
    private string $type;

    #[Groups('transaction:notification')]
    #[ORM\Column(type: 'string', nullable: true)]
    private string $notes;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
