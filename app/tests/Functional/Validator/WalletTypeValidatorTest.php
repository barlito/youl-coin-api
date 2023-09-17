<?php

declare(strict_types=1);

namespace App\Tests\Functional\Validator;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Validator\Entity\Wallet\WalletType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WalletTypeValidatorTest extends KernelTestCase
{
    public function setUp(): void
    {
        system(sprintf('bin/console hautelook:fixtures:load -n --env="test"'));
        self::bootKernel();
    }

    public function testWalletTypeConstraint()
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $walletBank = (new Wallet())
            ->setAmount('10100')
            ->setType(WalletTypeEnum::BANK)
        ;
        $violations = $validator->validate($walletBank);

        $this->assertCount(0, $violations);

        $entityManager->persist($walletBank);
        $entityManager->flush();

        $secondWalletBank = (new Wallet())
            ->setAmount('222222')
            ->setType(WalletTypeEnum::BANK)
        ;

        $violations = $validator->validate($secondWalletBank);

        $this->assertCount(1, $violations);
        $this->assertInstanceOf(ConstraintViolation::class, $violations[0]);
        $this->assertEquals(WalletType::UNIQUE_BANK_WALLET_ERROR, $violations[0]->getMessage());
    }
}
