<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\ApiUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Builder\TransactionBuilder;
use App\Service\Handler\TransactionHandler;
use App\Service\Util\MoneyUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Runs the handler against the real Doctrine outbox transport (the test env routes it to sync://)
class TransactionOutboxTest extends KernelTestCase
{
    private const string WALLET_FARPH = '01FPD1DRHVBMZEM5EGS95F5N3E';
    private const string WALLET_JUJU = '01FPD1DNKVFS5GGBPVXBT3YQ01';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->connection()->executeStatement("DELETE FROM messenger_messages WHERE queue_name = 'outbox'");
    }

    public function testTheOutboxMessageIsWrittenWithTheTransaction(): void
    {
        $transaction = $this->handler()->handleTransaction($this->buildTransaction('1000'));

        $messages = $this->outboxMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('TransactionCommitted', $messages[0]);
        $this->assertStringContainsString((string) $transaction->getId(), $messages[0]);
    }

    public function testNothingIsWrittenWhenTheTransactionIsRefused(): void
    {
        try {
            $this->handler()->handleTransaction($this->buildTransaction('999999999999999'));
            $this->fail('The transaction should have been refused.');
        } catch (ValidationException) {
        }

        $this->assertSame([], $this->outboxMessages());
        $this->assertSame('0', (string) $this->connection()->fetchOne('SELECT COUNT(*) FROM transaction WHERE external_identifier = :id', ['id' => 'outbox_test']));
    }

    public function testAnIdempotentReplayWritesNothingMore(): void
    {
        $handler = $this->handler();
        $first = $handler->handleTransaction($this->buildTransaction('1000'));
        $replay = $handler->handleTransaction($this->buildTransaction('1000'));

        $this->assertSame($first->getId(), $replay->getId());
        $this->assertCount(1, $this->outboxMessages());
    }

    private function handler(): TransactionHandler
    {
        $container = static::getContainer();
        $transport = new DoctrineTransportFactory($container->get('doctrine'))->createTransport(
            'doctrine://default?queue_name=outbox&auto_setup=false',
            [],
            new PhpSerializer(),
        );
        $bus = new MessageBus([new SendMessageMiddleware(new SendersLocator(
            [TransactionCommitted::class => ['outbox']],
            new ServiceLocator(['outbox' => static fn () => $transport]),
        ))]);

        return new TransactionHandler(
            $bus,
            $container->get(TransactionBuilder::class),
            $container->get(MoneyUtil::class),
            $container->get(TransactionRepository::class),
            $this->entityManager,
            $container->get(ValidatorInterface::class),
        );
    }

    private function buildTransaction(string $amount): Transaction
    {
        return new Transaction()
            ->setAmount($amount)
            ->setWalletFrom($this->entityManager->find(Wallet::class, self::WALLET_FARPH))
            ->setWalletTo($this->entityManager->find(Wallet::class, self::WALLET_JUJU))
            ->setType(TransactionTypeEnum::CLASSIC)
            ->setIssuer($this->entityManager->getRepository(ApiUser::class)->findOneBy([]))
            ->setExternalIdentifier('outbox_test')
        ;
    }

    /**
     * @return list<string>
     */
    private function outboxMessages(): array
    {
        return $this->connection()->fetchFirstColumn("SELECT body FROM messenger_messages WHERE queue_name = 'outbox'");
    }

    private function connection(): Connection
    {
        return $this->entityManager->getConnection();
    }
}
