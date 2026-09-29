<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Util\MoneyUtil;
use Twig\Attribute\AsTwigFilter;

class MoneyExtension
{
    public function __construct(private readonly MoneyUtil $moneyUtil)
    {
    }

    /**
     * @param numeric-string $minorAmount
     */
    #[AsTwigFilter('coins')]
    public function formatCoins(string $minorAmount): string
    {
        return $this->moneyUtil->getFormattedMoney($minorAmount);
    }
}
