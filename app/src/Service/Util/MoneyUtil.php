<?php

declare(strict_types=1);

namespace App\Service\Util;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use NumberFormatter;

class MoneyUtil
{
    public function getCurrency(): Currency
    {
        return new Currency(
            'YLC',
            0,
            'YoulCoin',
            8,
        );
    }

    public function getFormatter(): NumberFormatter
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, "\xC2\xA5\xC2\xA2");
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);

        return $formatter;
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function getMoney($amount): Money
    {
        return Money::ofMinor($amount, $this->getCurrency());
    }
}
