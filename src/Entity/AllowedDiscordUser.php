<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AllowedDiscordUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dynamic part of the Discord whitelist, managed from the admin.
 *
 * The static list in the "app.allowed_discord_users" parameter stays as a bootstrap
 * safety net: it cannot be edited at runtime, so emptying this table can never lock
 * everyone out of the app.
 */
#[ORM\Entity(repositoryClass: AllowedDiscordUserRepository::class)]
#[UniqueEntity(fields: ['discordId'])]
class AllowedDiscordUser implements \Stringable
{
    use TimestampableEntity;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{17,20}$/', message: 'This is not a valid Discord user ID (snowflake).')]
    #[ORM\Id]
    #[ORM\Column(type: 'string', unique: true)]
    private string $discordId;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $label = null;

    public function __toString(): string
    {
        return null === $this->label ? $this->discordId : $this->label . ' | ' . $this->discordId;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
