<?php

declare(strict_types=1);

namespace App\Tests\Functional\DatabaseConstraint;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WalletDbConstraintTest extends KernelTestCase
{
    public function setUp(): void
    {
        system(sprintf('bin/console hautelook:fixtures:load -n --env="test"'));
        self::bootKernel();
    }

    public function testWalletTypeDbConstraint()
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $walletBank = (new Wallet())
            ->setAmount('10100')
            ->setType(WalletTypeEnum::BANK)
            ->setName('Bank wallet')
        ;

        $this->expectException(UniqueConstraintViolationException::class);
        $this->expectExceptionCode(7);

        $entityManager->persist($walletBank);
        $entityManager->flush();
    }
}
