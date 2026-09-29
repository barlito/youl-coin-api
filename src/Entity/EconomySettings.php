<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EconomySettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// Singleton row (id always SINGLETON_ID, enforced by a DB check constraint): global economy tuning, edited in the admin
#[ORM\Entity(repositoryClass: EconomySettingsRepository::class)]
class EconomySettings
{
    public const int SINGLETON_ID = 1;

    private const int MINOR_UNITS_PER_COIN = 100_000_000;

    #[ORM\Id]
    #[ORM\Column(type: 'smallint')]
    private int $id = self::SINGLETON_ID;

    // Minor units (1 coin = 10^8), 0 = disabled. Admin edits/reads this through welcomeBonusAmountCoins
    #[ORM\Column(type: 'string')]
    private string $welcomeBonusAmount = '100000000000';

    public function getId(): int
    {
        return $this->id;
    }

    public function getWelcomeBonusAmount(): string
    {
        return $this->welcomeBonusAmount;
    }

    public function setWelcomeBonusAmount(string $welcomeBonusAmount): self
    {
        $this->welcomeBonusAmount = $welcomeBonusAmount;

        return $this;
    }

    #[Assert\PositiveOrZero]
    public function getWelcomeBonusAmountCoins(): int
    {
        return (int) bcdiv($this->welcomeBonusAmount, (string) self::MINOR_UNITS_PER_COIN);
    }

    public function setWelcomeBonusAmountCoins(int $welcomeBonusAmountCoins): self
    {
        $this->welcomeBonusAmount = bcmul((string) $welcomeBonusAmountCoins, (string) self::MINOR_UNITS_PER_COIN);

        return $this;
    }
}
