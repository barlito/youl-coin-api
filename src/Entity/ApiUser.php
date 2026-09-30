<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\IdUuidTrait;
use App\Enum\Roles\ApiUserRoleEnum;
use App\Repository\ApiUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ApiUserRepository::class)]
class ApiUser implements UserInterface, \Stringable
{
    use IdUuidTrait;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $name = null;

    // Name of the app shown to players in their history
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $apiKeyHash;

    #[ORM\Column(length: 6)]
    private string $apiKeyPrefix;

    #[ORM\Column]
    private array $roles = [];

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $trimmed = null === $displayName ? null : trim($displayName);
        $this->displayName = '' === $trimmed ? null : $trimmed;

        return $this;
    }

    public function getPublicName(): ?string
    {
        return $this->displayName ?? $this->name;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->name;
    }

    public static function hashApiKey(#[\SensitiveParameter] string $plainApiKey): string
    {
        return hash('sha256', $plainApiKey);
    }

    public function getApiKeyPrefix(): string
    {
        return $this->apiKeyPrefix;
    }

    // Only the hash and a recognisable prefix are kept: the key itself is never stored
    public function setPlainApiKey(#[\SensitiveParameter] string $plainApiKey): static
    {
        $this->apiKeyHash = self::hashApiKey($plainApiKey);
        $this->apiKeyPrefix = substr($plainApiKey, 0, 6);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = ApiUserRoleEnum::ROLE_USER->value;

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
