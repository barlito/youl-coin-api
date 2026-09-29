<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Message\TransactionCommitted;
use App\Repository\DiscordUserRepository;
use App\Repository\TransactionRepository;
use Doctrine\DBAL\Connection;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailedNotificationsControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM messenger_messages');
        $this->login('188967649332428800');
    }

    public function testTheEmptyStateIsShown(): void
    {
        $this->client->request('GET', $this->url());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucune notification en échec');
        self::assertSelectorTextContains('body', 'En échec définitif : 0');
    }

    public function testAFailedTransactionNotificationIsListedWithItsDetails(): void
    {
        $transactionId = (string) static::getContainer()->get(TransactionRepository::class)->findOneBy(['externalIdentifier' => 'fixture'])?->getId();
        $failed = static::getContainer()->get('messenger.receiver_locator')->get('failed');
        \assert($failed instanceof TransportInterface);
        $failed->send(new Envelope(new TransactionCommitted($transactionId), [
            new ErrorDetailsStamp(\RuntimeException::class, 0, 'Discord webhook unreachable'),
            new RedeliveryStamp(5),
        ]));

        $crawler = $this->client->request('GET', $this->url());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'En échec définitif : 1');
        $row = $crawler->filter('tbody tr');
        $this->assertCount(1, $row);
        $this->assertStringContainsString('TransactionCommitted', $row->text());
        $this->assertStringContainsString('Discord webhook unreachable', $row->text());
        $this->assertStringContainsString('5', $row->filter('td')->last()->text());
        $this->assertCount(1, $row->filter(\sprintf('a[href*="%s"]', $transactionId)));
        self::assertSelectorTextContains('body', 'messenger:failed:retry');
    }

    public function testANonAdminIsDenied(): void
    {
        $this->login('195659530363731968');

        $this->client->request('GET', $this->url());

        self::assertResponseStatusCodeSame(403);
    }

    private function url(): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)->setRoute('admin_failed_notifications')->generateUrl();
    }

    private function login(string $discordId): void
    {
        $user = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => $discordId]);
        \assert(null !== $user);
        $this->client->loginUser($user);
    }
}
