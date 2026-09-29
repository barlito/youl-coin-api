<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Messenger\Handler;

use App\Entity\Transaction;
use App\Message\TransactionCommitted;
use App\Repository\TransactionRepository;
use App\Service\Messenger\Handler\TransactionCommittedHandler;
use App\Service\Messenger\Publisher\TransactionNotificationPublisher;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;

// Consumes the real Doctrine outbox queue (the test env routes it to sync://)
class TransactionCommittedRetryTest extends KernelTestCase
{
    private DoctrineTransport $transport;

    private Connection $connection;

    protected function setUp(): void
    {
        system('bin/console hautelook:fixtures:load -n --env="test"');

        $container = static::getContainer();
        $this->connection = $container->get('doctrine')->getConnection();
        $this->connection->executeStatement("DELETE FROM messenger_messages WHERE queue_name = 'outbox'");
        $this->transport = new DoctrineTransportFactory($container->get('doctrine'))->createTransport(
            'doctrine://default?queue_name=outbox&auto_setup=false',
            [],
            new PhpSerializer(),
        );
    }

    public function testAFailingNotifierSchedulesARetryInsteadOfLosingTheMessage(): void
    {
        $notifier = $this->createStub(TransactionNotifierInterface::class);
        $notifier->method('notifyNewTransaction')->willThrowException(new \RuntimeException('Discord is down'));

        $this->transport->send(new Envelope(new TransactionCommitted($this->anyTransactionId())));
        $this->consumeOne($notifier);

        $pending = $this->pending();
        $this->assertCount(1, $pending);
        $this->assertSame(1, $pending[0]->last(RedeliveryStamp::class)?->getRetryCount());
    }

    public function testAHealthyNotifierAcknowledgesTheMessage(): void
    {
        $this->transport->send(new Envelope(new TransactionCommitted($this->anyTransactionId())));
        $this->consumeOne($this->createStub(TransactionNotifierInterface::class));

        $this->assertCount(0, $this->pending());
    }

    private function consumeOne(TransactionNotifierInterface $notifier): void
    {
        $container = static::getContainer();
        $handler = new TransactionCommittedHandler(
            $container->get(TransactionRepository::class),
            $notifier,
            $this->createStub(TransactionNotificationPublisher::class),
        );
        $senders = new ServiceLocator(['outbox' => fn () => $this->transport]);
        $bus = new MessageBus([
            new SendMessageMiddleware(new SendersLocator([], $senders)),
            new HandleMessageMiddleware(new HandlersLocator([TransactionCommitted::class => [$handler]])),
        ]);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener($senders, new ServiceLocator(['outbox' => fn () => new MultiplierRetryStrategy(5, 0)])));
        $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));

        new Worker(['outbox' => $this->transport], $bus, $dispatcher)->run(['sleep' => 0]);
    }

    /**
     * @return list<Envelope>
     */
    private function pending(): array
    {
        return array_values(iterator_to_array($this->transport->all(), false));
    }

    private function anyTransactionId(): string
    {
        $transaction = static::getContainer()->get(TransactionRepository::class)->findOneBy([]);
        $this->assertInstanceOf(Transaction::class, $transaction);

        return (string) $transaction->getId();
    }
}
