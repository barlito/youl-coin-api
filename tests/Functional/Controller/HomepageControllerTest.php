<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomepageControllerTest extends WebTestCase
{
    private const string BULK_PLAYER_ID = '500000000000000001';
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';
    private const string WEEBY_WALLET_ID = '01FPD1E1T67SBB4EWATRB9DAJV';

    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAnAnonymousVisitorIsSentToTheDiscordLogin(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('http://localhost/connect/discord');
    }

    public function testAPlayerSeesTheirBalanceAndTheirCounterparts(): void
    {
        $this->addTransaction(self::BANK_WALLET_ID, $this->bulkWallet()->getId(), TransactionTypeEnum::WELCOME_BONUS, '100000000', '-1 hour');
        $this->loginAsBulkPlayer();

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'WalletHistoryBulk');
        self::assertSelectorTextContains('.balance-amount', '5.61');
        self::assertSelectorExists('a[href="/logout"]');
        self::assertSelectorExists('a[href="https://ytcg.youlz.fr"]');
        $newestRow = $crawler->filter('tbody tr')->first()->text();
        $this->assertStringContainsString('Bonus de bienvenue', $newestRow);
        $this->assertStringContainsString('Banque', $newestRow);
        $this->assertStringContainsString('+', $newestRow);
        $this->assertStringContainsString('1.00', $newestRow);
        $secondRow = $crawler->filter('tbody tr')->eq(1)->text();
        $this->assertStringContainsString('Warny', $secondRow);
        $this->assertStringContainsString('02/02/2026 10:00', $secondRow);
    }

    public function testTheHistoryIsPaginatedByTwenty(): void
    {
        $this->loginAsBulkPlayer();

        $crawler = $this->client->request('GET', '/');
        $this->assertCount(20, $crawler->filter('tbody tr'));
        self::assertSelectorTextContains('.pagination', 'Page 1 sur 2');
        self::assertSelectorNotExists('.pagination a[rel="prev"]');

        $crawler = $this->client->request('GET', '/?page=2');
        self::assertResponseIsSuccessful();
        $this->assertCount(13, $crawler->filter('tbody tr'));
        self::assertSelectorTextContains('.pagination', 'Page 2 sur 2');
        self::assertSelectorNotExists('.pagination a[rel="next"]');

        $this->client->request('GET', '/?page=3');
        self::assertResponseStatusCodeSame(404);
    }

    public function testNothingInternalLeaksIntoThePage(): void
    {
        $transaction = $this->addTransaction(self::WEEBY_WALLET_ID, $this->bulkWallet()->getId(), TransactionTypeEnum::CLASSIC, '100000000', '-1 hour');
        $transaction->setExternalIdentifier('internal-external-identifier')->setReason('internal-reason');
        $this->entityManager->flush();
        $this->loginAsBulkPlayer();

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('internal-external-identifier', $html);
        $this->assertStringNotContainsString('internal-reason', $html);
        $this->assertStringNotContainsString((string) $this->bulkWallet()->getId(), $html);
    }

    public function testTheThirtyDayTotalsIgnoreOlderTransactions(): void
    {
        $bulkWalletId = $this->bulkWallet()->getId();
        $this->addTransaction(self::WEEBY_WALLET_ID, $bulkWalletId, TransactionTypeEnum::CLASSIC, '300000000', '-2 hours');
        $this->addTransaction(self::BANK_WALLET_ID, $bulkWalletId, TransactionTypeEnum::AIR_DROP, '100000000', '-29 days');
        $this->addTransaction(self::BANK_WALLET_ID, $bulkWalletId, TransactionTypeEnum::AIR_DROP, '9900000000', '-31 days');
        $this->addTransaction($bulkWalletId, self::WEEBY_WALLET_ID, TransactionTypeEnum::CLASSIC, '150000000', '-1 hour');
        $this->addTransaction($bulkWalletId, self::WEEBY_WALLET_ID, TransactionTypeEnum::CLASSIC, '7700000000', '-40 days');
        $this->loginAsBulkPlayer();

        $this->client->request('GET', '/');

        self::assertSelectorTextContains('.amount-in', '4.00');
        self::assertSelectorTextContains('.amount-out', '1.50');
    }

    public function testTheQueryCountDoesNotGrowWithTheCounterparts(): void
    {
        $this->loginAsBulkPlayer();
        $this->client->enableProfiler();
        $this->client->request('GET', '/');
        $baseline = $this->queryCount();

        foreach (['01FPD1DHMWPV4BHJQ82TSJEBJC', '01FPD1DNKVFS5GGBPVXBT3YQ01', '01FPD1DRHVBMZEM5EGS95F5N3E', '01FPD1DYNAMHV939ARCCRPK864'] as $walletId) {
            $this->addTransaction($walletId, $this->bulkWallet()->getId(), TransactionTypeEnum::CLASSIC, '100000000', '-1 hour');
        }

        $this->client->enableProfiler();
        $crawler = $this->client->request('GET', '/');

        $this->assertCount(20, $crawler->filter('tbody tr'));
        $this->assertSame($baseline, $this->queryCount());
    }

    public function testAPlayerWithoutWalletSeesAClearMessage(): void
    {
        $player = (new DiscordUser())->setDiscordId('500000000000000099')->setUsername('NoWalletPlayer');
        $this->entityManager->persist($player);
        $this->entityManager->flush();
        $this->client->loginUser($player);

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'NoWalletPlayer');
        self::assertSelectorTextContains('.notice', 'pas encore de portefeuille');
        self::assertSelectorNotExists('.balance-amount');
    }

    private function queryCount(): int
    {
        /** @var DoctrineDataCollector $collector */
        $collector = $this->client->getProfile()?->getCollector('db') ?? throw new \LogicException('Profiler is disabled.');

        return $collector->getQueryCount();
    }

    private function loginAsBulkPlayer(): void
    {
        $this->client->loginUser($this->entityManager->find(DiscordUser::class, self::BULK_PLAYER_ID) ?? throw new \LogicException('Missing fixture player.'));
    }

    private function bulkWallet(): Wallet
    {
        return $this->entityManager->find(DiscordUser::class, self::BULK_PLAYER_ID)?->getWallet() ?? throw new \LogicException('Missing fixture wallet.');
    }

    private function addTransaction(?string $fromWalletId, ?string $toWalletId, TransactionTypeEnum $type, string $amount, string $ago): Transaction
    {
        $transaction = (new Transaction())
            ->setWalletFrom($fromWalletId ? $this->entityManager->find(Wallet::class, $fromWalletId) : null)
            ->setWalletTo($toWalletId ? $this->entityManager->find(Wallet::class, $toWalletId) : null)
            ->setType($type)
            ->setAmount($amount)
            ->setCreatedAt(new \DateTime($ago))
        ;
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }
}
