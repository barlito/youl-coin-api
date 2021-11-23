<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DiscordUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DiscordUserRepository::class)
 */
class DiscordUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     */
    private string $discordId;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class, mappedBy="discordUser")
     */
    private Wallet $wallet;

    public function getDiscordId(): string
    {
        return $this->discordId;
    }

    public function setDiscordId(string $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function setWallet(Wallet $wallet): self
    {
        if ($wallet->getDiscordUser() !== $this) {
            $wallet->setDiscordUser($this);
        }

        $this->wallet = $wallet;

        return $this;
    }
}
