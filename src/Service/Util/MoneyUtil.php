<?php

declare(strict_types=1);

namespace App\Service\Util;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;

class MoneyUtil
{
    public const CURRENCY_SYMBOL = "\xC2\xA5\xC2\xA2";

    public function getCurrency(): Currency
    {
        return new Currency(
            'YLC',
            0,
            'YoulCoin',
            8,
        );
    }

    public function getFormatter(): \NumberFormatter
    {
        $formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, self::CURRENCY_SYMBOL);
        // Two decimals minimum, up to the 8 of the currency: a non-zero amount is never displayed as 0.00
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 8);

        return $formatter;
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function getMoney(string $amount): Money
    {
        return Money::ofMinor($amount, $this->getCurrency(), roundingMode: RoundingMode::HALF_CEILING);
    }

    /**
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function coinsToMinor(string $coins): string
    {
        return (string) Money::of($coins, $this->getCurrency(), roundingMode: RoundingMode::UNNECESSARY)->getMinorAmount();
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function minorToCoins(string $minor): string
    {
        return (string) $this->getMoney($minor)->getAmount();
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function getFormattedMoney(string $amount): string
    {
        $formattedAmount = $this->getMoney($amount)->formatWith($this->getFormatter());

        return self::CURRENCY_SYMBOL . str_replace('YLC', '', $formattedAmount);
    }
}
