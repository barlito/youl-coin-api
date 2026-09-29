<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Util;

use App\Service\Util\MoneyUtil;
use Brick\Math\Exception\MathException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyUtilTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validAmounts(): iterable
    {
        yield 'integer' => ['5', '500000000'];
        yield 'half a coin' => ['0.5', '50000000'];
        yield 'smallest unit' => ['0.00000001', '1'];
        yield 'eight decimals' => ['1.23456789', '123456789'];
        yield 'zero' => ['0', '0'];
    }

    #[DataProvider('validAmounts')]
    public function testCoinsAreConvertedToMinorUnits(string $coins, string $expected): void
    {
        $this->assertSame($expected, new MoneyUtil()->coinsToMinor($coins));
    }

    #[DataProvider('validAmounts')]
    public function testMinorUnitsAreConvertedBackToCoins(string $coins, string $minor): void
    {
        $this->assertSame((string) (float) $coins, (string) (float) new MoneyUtil()->minorToCoins($minor));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAmounts(): iterable
    {
        yield 'nine decimals' => ['0.000000001'];
        yield 'empty' => [''];
        yield 'not a number' => ['abc'];
    }

    #[DataProvider('invalidAmounts')]
    public function testAnAmountThatIsNotExactlyRepresentableIsRefused(string $coins): void
    {
        $this->expectException(MathException::class);

        new MoneyUtil()->coinsToMinor($coins);
    }
}
