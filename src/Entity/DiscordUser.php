<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\DiscordUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass=DiscordUserRepository::class)
 */
#[ApiResource(
    collectionOperations: ['get'],
    itemOperations: ['get'],
)]
class DiscordUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    private ?string $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $discordId;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class, mappedBy="discordUser")
     */
    private Wallet $wallet;

    public function getId(): ?string
    {
        return $this->id;
    }

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
