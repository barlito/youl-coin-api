<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\IdUlidTrait;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=WalletRepository::class)
 */
class Wallet
{
    use IdUlidTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $amount;

    /**
     * @ORM\OneToOne(targetEntity=DiscordUser::class, inversedBy="wallet")
     * @ORM\JoinColumn(referencedColumnName="discord_id")
     */
    private ?DiscordUser $discordUser;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(WalletTypeEnum::VALUES)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
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
