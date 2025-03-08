<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Roles\RoleEnum;
use App\Repository\DiscordUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DiscordUserRepository::class)]
class DiscordUser implements UserInterface
{
    #[Groups('transaction:notification')]
    #[ORM\Id]
    #[ORM\Column(type: 'string', unique: true)]
    private string $discordId;

    #[ORM\OneToOne(mappedBy: 'discordUser', targetEntity: Wallet::class)]
    private Wallet $wallet;

    #[Groups('transaction:notification')]
    #[ORM\Column(type: 'string')]
    private string $username;

    #[ORM\Column]
    private array $roles = [];

    public function __toString(): string
    {
        return $this->getUsername() . ' | ' . $this->getDiscordId();
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $name): self
    {
        $this->username = $name;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = RoleEnum::ROLE_USER->value;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
