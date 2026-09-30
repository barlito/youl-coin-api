<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\TransactionTypeEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransactionTypeEnumTest extends TestCase
{
    #[DataProvider('appTypes')]
    public function testAppTypesHaveAFrenchLabelAndAreNotSystemOnly(TransactionTypeEnum $type, string $label): void
    {
        $this->assertSame($label, $type->getLabel());
        $this->assertFalse($type->isSystemOnly());
    }

    /**
     * @return iterable<string, array{TransactionTypeEnum, string}>
     */
    public static function appTypes(): iterable
    {
        yield 'purchase' => [TransactionTypeEnum::PURCHASE, 'Achat en boutique'];
        yield 'reward' => [TransactionTypeEnum::REWARD, 'Récompense'];
        yield 'market payment' => [TransactionTypeEnum::MARKET_PAYMENT, 'Achat au marché'];
        yield 'market payout' => [TransactionTypeEnum::MARKET_PAYOUT, 'Vente au marché'];
        yield 'market refund' => [TransactionTypeEnum::MARKET_REFUND, 'Remboursement marché'];
    }

    public function testAPlayerTransferIsLabelledAsATransfer(): void
    {
        $this->assertSame('Virement', TransactionTypeEnum::CLASSIC->getLabel());
    }

    public function testEveryLabelIsUnique(): void
    {
        $labels = array_map(static fn (TransactionTypeEnum $type): string => $type->getLabel(), TransactionTypeEnum::cases());

        $this->assertCount(\count($labels), array_unique($labels));
    }
}
