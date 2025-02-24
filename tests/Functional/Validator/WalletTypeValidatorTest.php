<?php

declare(strict_types=1);

namespace App\Tests\Functional\Validator;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Validator\Entity\Wallet\WalletType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WalletTypeValidatorTest extends KernelTestCase
{
    public function setUp(): void
    {
        system(\sprintf('bin/console hautelook:fixtures:load -n --env="test"'));
        self::bootKernel();
    }

    public function testWalletTypeConstraint(): void
    {
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $secondWalletBank = (new Wallet())
            ->setAmount('222222')
            ->setType(WalletTypeEnum::BANK)
        ;

        $violations = $validator->validate($secondWalletBank, groups: ['wallet:create']);

        $this->assertCount(1, $violations);
        $this->assertInstanceOf(ConstraintViolation::class, $violations[0]);
        $this->assertEquals(WalletType::UNIQUE_BANK_WALLET_ERROR, $violations[0]->getMessage());
    }
}
