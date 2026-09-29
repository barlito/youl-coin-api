<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Entity\EconomySettings;
use App\Repository\EconomySettingsRepository;

class EconomySettingsProvider
{
    public function __construct(private readonly EconomySettingsRepository $economySettingsRepository)
    {
    }

    // Falls back to the seeded default if the singleton row was somehow deleted
    public function getWelcomeBonusAmount(): string
    {
        return $this->economySettingsRepository->find(EconomySettings::SINGLETON_ID)?->getWelcomeBonusAmount()
            ?? '100000000000';
    }
}
