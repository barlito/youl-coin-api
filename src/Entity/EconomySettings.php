<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EconomySettingsRepository;
use App\Service\Util\MoneyUtil;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// Singleton row (id always SINGLETON_ID, enforced by a DB check constraint): global economy tuning, edited in the admin
#[ORM\Entity(repositoryClass: EconomySettingsRepository::class)]
class EconomySettings
{
    public const int SINGLETON_ID = 1;

    // Minor units (1000 coins)
    public const string DEFAULT_WELCOME_BONUS_AMOUNT = '100000000000';

    public const int MAX_WELCOME_BONUS_COINS = 100_000;

    #[ORM\Id]
    #[ORM\Column(type: 'smallint')]
    private int $id = self::SINGLETON_ID;

    // Minor units (1 coin = 10^8), 0 = disabled. Admin edits/reads this through welcomeBonusAmountCoins
    #[ORM\Column(type: 'string')]
    private string $welcomeBonusAmount = self::DEFAULT_WELCOME_BONUS_AMOUNT;

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

    #[Assert\Range(min: 0, max: self::MAX_WELCOME_BONUS_COINS)]
    public function getWelcomeBonusAmountCoins(): int
    {
        return (int) new MoneyUtil()->minorToCoins($this->welcomeBonusAmount);
    }

    public function setWelcomeBonusAmountCoins(int $welcomeBonusAmountCoins): self
    {
        $this->welcomeBonusAmount = new MoneyUtil()->coinsToMinor((string) $welcomeBonusAmountCoins);

        return $this;
    }
}
