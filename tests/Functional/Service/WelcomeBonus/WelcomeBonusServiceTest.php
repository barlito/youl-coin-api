<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\WelcomeBonus;

use App\Entity\DiscordUser;
use App\Entity\EconomySettings;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Repository\DiscordUserRepository;
use App\Repository\EconomySettingsRepository;
use App\Repository\TransactionRepository;
use App\Repository\WalletRepository;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Util\MoneyUtil;
use App\Service\WelcomeBonus\WelcomeBonusService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\AbstractLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WelcomeBonusServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private AbstractLogger $logger;

    /** @var list<array{string, string}> */
    private array $logs = [];

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->logs = [];
        $this->logger = new class ($this->logs) extends AbstractLogger {
            /** @param list<array{string, string}> $logs */
            public function __construct(private array &$logs)
            {
            }

            public function log($level, string | \Stringable $message, array $context = []): void
            {
                $this->logs[] = [(string) $level, (string) $message];
            }
        };
    }

    public function testAWalletGetsTheBonusOnlyOnce(): void
    {
        $user = $this->farph();

        $this->service()->grantIfEligible($user);
        $this->service()->grantIfEligible($user);

        $this->assertSame(1, $this->bonusCount($user));
        $this->assertSame([], $this->logs);
    }

    public function testAMissingSettingsRowDisablesTheBonusAndLogsAnError(): void
    {
        $this->entityManager->remove($this->entityManager->find(EconomySettings::class, EconomySettings::SINGLETON_ID));
        $this->entityManager->flush();
        $user = $this->farph();

        $this->service()->grantIfEligible($user);

        $this->assertSame(0, $this->bonusCount($user));
        $this->assertSame([['error', 'Welcome bonus disabled: the economy settings row is missing.']], $this->logs);
    }

    public function testAFailingOutboxWriteRollsTheGrantBackAndIsLoggedAsAnError(): void
    {
        $user = $this->farph();
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new \RuntimeException('Outbox unavailable'));

        $this->service($bus)->grantIfEligible($user);

        $this->assertSame(0, $this->bonusCount($user));
        $this->assertSame([['error', 'Welcome bonus not granted, login continues.']], $this->logs);
    }

    public function testARejectedGrantIsLoggedAsAnError(): void
    {
        $this->entityManager->getConnection()->executeStatement("UPDATE wallet SET amount = '0' WHERE type = 'bank'");
        $user = $this->farph();

        $this->service()->grantIfEligible($user);

        $this->assertSame(0, $this->bonusCount($user));
        $this->assertSame([['error', 'Welcome bonus not granted, login continues.']], $this->logs);
    }

    private function service(?MessageBusInterface $bus = null): WelcomeBonusService
    {
        $container = static::getContainer();
        $handler = new TransactionHandler(
            $bus ?? new MessageBus(),
            $container->get(TransactionBuilder::class),
            $container->get(MoneyUtil::class),
            $container->get(TransactionRepository::class),
            $this->entityManager,
            $container->get(ValidatorInterface::class),
        );

        return new WelcomeBonusService(
            $container->get(EconomySettingsRepository::class),
            $container->get(WalletRepository::class),
            $container->get(TransactionRepository::class),
            $handler,
            $this->logger,
        );
    }

    private function farph(): DiscordUser
    {
        $user = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => '188967949963362304']);
        $this->assertInstanceOf(DiscordUser::class, $user);

        return $user;
    }

    private function bonusCount(DiscordUser $user): int
    {
        $wallet = $user->getWallet();
        $this->assertInstanceOf(Wallet::class, $wallet);

        return static::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM transaction WHERE wallet_to_id = :wallet AND type = :type',
            ['wallet' => $wallet->getId(), 'type' => TransactionTypeEnum::WELCOME_BONUS->value],
            ['wallet' => 'ulid'],
        )->fetchOne();
    }
}
