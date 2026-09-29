<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\TransactionCrudController;
use App\Controller\Admin\WalletCrudController;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Repository\DiscordUserRepository;
use App\Repository\TransactionRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TransactionCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->client->loginUser($this->getUser('188967649332428800'));
    }

    public function testTheListShowsTheTransactionsMostRecentFirst(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(
            (new Transaction())->setType(TransactionTypeEnum::AIR_DROP)->setAmount('1')
                ->setWalletFrom($this->walletByName('Bank Wallet'))->setWalletTo($this->walletByName('Wallet Veli'))
                ->setCreatedAt(new \DateTime('2099-01-01 12:00:00')),
        );
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->crudUrl(Action::INDEX));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Transfert classique');
        self::assertSelectorTextContains('body', 'test');
        $this->assertStringContainsString('01/01/2099 13:00:00', $crawler->filter('tbody tr')->first()->text());
        self::assertSelectorTextContains('body', 'Air drop');
        $this->assertCount(20, $crawler->filter('tbody tr'));
    }

    public function testANonAdminIsDenied(): void
    {
        $this->client->loginUser($this->getUser('195659530363731968'));

        $this->client->request('GET', $this->crudUrl(Action::INDEX));

        self::assertResponseStatusCodeSame(403);
    }

    public function testTheLedgerCannotBeWrittenFromTheAdmin(): void
    {
        $crawler = $this->client->request('GET', $this->crudUrl(Action::INDEX));
        self::assertSelectorNotExists('.action-new');
        self::assertSelectorNotExists('.action-edit');
        self::assertSelectorNotExists('.action-delete');
        self::assertSelectorNotExists('.batch-actions');
        $this->assertCount(0, $crawler->filter('input[type="checkbox"].form-batch-checkbox'));

        $id = (string) $this->transactionByExternalIdentifier('fixture')->getId();
        foreach ([Action::NEW, Action::EDIT, Action::DELETE] as $action) {
            $this->client->request('GET', $this->crudUrl($action, $id));
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testTheWalletFilterReturnsSentAndReceivedTransactionsOfThatWalletOnly(): void
    {
        $farph = $this->walletByName('Wallet Farph');

        $crawler = $this->client->request('GET', $this->walletFilterUrl($farph));

        self::assertResponseIsSuccessful();
        $this->assertEqualsCanonicalizing(
            ['fixture_issued_by_test', 'fixture_issued_by_other'],
            $this->listedIds($crawler, [
                $this->transactionByExternalIdentifier('fixture_issued_by_test'),
                $this->transactionByExternalIdentifier('fixture_issued_by_other'),
                $this->transactionByExternalIdentifier('fixture'),
            ]),
        );
    }

    public function testTheDetailShowsTheReasonAndTheExternalIdentifier(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $bank = $this->walletByName('Bank Wallet');
        $mint = (new Transaction())
            ->setType(TransactionTypeEnum::MINT)
            ->setAmount('500000000')
            ->setWalletTo($bank)
            ->setReason('Genesis top-up')
            ->setExternalIdentifier('mint-ext-1')
            ->setInitiatedBy($this->getUser('188967649332428800'))
        ;
        $entityManager->persist($mint);
        $entityManager->flush();

        $this->client->request('GET', $this->crudUrl(Action::DETAIL, (string) $mint->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Genesis top-up');
        self::assertSelectorTextContains('body', 'mint-ext-1');
        self::assertSelectorTextContains('body', (string) $mint->getId());
        self::assertSelectorTextContains('body', 'Mint');
    }

    public function testTheWalletCrudLinksToTheFilteredTransactions(): void
    {
        $farph = $this->walletByName('Wallet Farph');
        $generator = static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(WalletCrudController::class)
        ;

        $indexCrawler = $this->client->request('GET', (clone $generator)->setAction(Action::INDEX)->generateUrl());
        $detailCrawler = $this->client->request('GET', (clone $generator)->setAction(Action::DETAIL)->setEntityId($farph->getId())->generateUrl());

        $links = [
            $indexCrawler->filter(\sprintf('tr[data-id="%s"] a.action-transactions', $farph->getId())),
            $detailCrawler->filter('a.action-transactions'),
        ];
        foreach ($links as $link) {
            $this->assertCount(1, $link);
            $crawler = $this->client->click($link->link());

            self::assertResponseIsSuccessful();
            $this->assertCount(2, $crawler->filter('tbody tr'));
            self::assertSelectorTextContains('tbody', 'Wallet Farph');
        }
    }

    public function testTheTypeFilterKeepsOnlyThatType(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(
            (new Transaction())->setType(TransactionTypeEnum::MINT)->setAmount('1')->setWalletTo($this->walletByName('Bank Wallet'))->setReason('Test'),
        );
        $entityManager->flush();

        $url = static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(TransactionCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters', ['type' => ['comparison' => '=', 'value' => TransactionTypeEnum::MINT->value]])
            ->generateUrl()
        ;
        $crawler = $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('tbody tr'));
        self::assertSelectorTextContains('tbody', 'Mint');
    }

    public function testTheListQueryCountDoesNotGrowWithTheNumberOfRows(): void
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $this->crudUrl(Action::INDEX));
        $before = $this->queryCount();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $bank = $this->walletByName('Bank Wallet');
        foreach (['A', 'B', 'C'] as $suffix) {
            $wallet = (new Wallet())->setName('Extra ' . $suffix)->setType(WalletTypeEnum::USER);
            $entityManager->persist($wallet);
            $entityManager->persist(
                (new Transaction())->setType(TransactionTypeEnum::CLASSIC)->setAmount('1')->setWalletFrom($wallet)->setWalletTo($bank)
                    ->setInitiatedBy($this->getUser('195659530363731968')),
            );
        }
        $entityManager->flush();

        $this->client->enableProfiler();
        $this->client->request('GET', $this->crudUrl(Action::INDEX));

        $this->assertSame($before, $this->queryCount());
    }

    private function queryCount(): int
    {
        $profile = $this->client->getProfile();
        \assert(false !== $profile);

        return $profile->getCollector('db')->getQueryCount();
    }

    /**
     * @param list<Transaction> $candidates
     *
     * @return list<string> external identifiers of the candidates listed on the page
     */
    private function listedIds(Crawler $crawler, array $candidates): array
    {
        $listed = $crawler->filter('tbody tr')->each(static fn (Crawler $row): string => $row->attr('data-id') ?? '');

        $identifiers = [];
        foreach ($candidates as $transaction) {
            if (\in_array((string) $transaction->getId(), $listed, true)) {
                $identifiers[] = (string) $transaction->getExternalIdentifier();
            }
        }

        $this->assertCount(2, $listed);

        return $identifiers;
    }

    private function walletFilterUrl(Wallet $wallet): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(TransactionCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters', [TransactionCrudController::WALLET_FILTER => ['comparison' => '=', 'value' => $wallet->getId()]])
            ->generateUrl()
        ;
    }

    private function crudUrl(string $action, ?string $entityId = null): string
    {
        $generator = static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(TransactionCrudController::class)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $generator->setEntityId($entityId);
        }

        return $generator->generateUrl();
    }

    private function walletByName(string $name): Wallet
    {
        $wallet = static::getContainer()->get(WalletRepository::class)->findOneBy(['name' => $name]);
        \assert($wallet instanceof Wallet);

        return $wallet;
    }

    private function transactionByExternalIdentifier(string $externalIdentifier): Transaction
    {
        $transaction = static::getContainer()->get(TransactionRepository::class)->findOneBy(['externalIdentifier' => $externalIdentifier]);
        \assert($transaction instanceof Transaction);

        return $transaction;
    }

    private function getUser(string $discordId): \Symfony\Component\Security\Core\User\UserInterface
    {
        $user = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => $discordId]);
        \assert(null !== $user);

        return $user;
    }
}
