<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\DiscordUserCrudController;
use App\Entity\AllowedDiscordUser;
use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Repository\DiscordUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DiscordUserCrudControllerTest extends WebTestCase
{
    private const string BARLITO = '188967649332428800';
    private const string JUJU = '195659530363731968';

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->client->loginUser($this->getUser(self::BARLITO));
    }

    public function testTheListShowsUsernameRolesBalanceAndWhitelistStatusSortedByUsername(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist((new AllowedDiscordUser())->setDiscordId('500000000000000001'));
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->crudUrl(Action::INDEX));

        self::assertResponseIsSuccessful();
        $usernames = $crawler->filter('tbody tr td[data-column="username"]')->each(static fn (Crawler $cell): string => trim($cell->text()));
        $sorted = $usernames;
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), $usernames);

        $barlito = $crawler->filter(\sprintf('tr[data-id="%s"]', self::BARLITO))->text();
        $this->assertStringContainsString('ROLE_ADMIN', $barlito);
        $this->assertStringContainsString('9,000.00', $barlito);
        $this->assertStringContainsString('Oui (config)', $barlito);
        $this->assertStringContainsString('Oui (admin)', $crawler->filter('tr[data-id="500000000000000001"]')->text());
    }

    public function testAPlayerOutsideBothWhitelistsIsShownAsRevoked(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist((new DiscordUser())->setDiscordId('400000000000000009')->setUsername('Ghost'));
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->crudUrl(Action::INDEX));

        $this->assertStringContainsString('Non — révoqué', $crawler->filter('tr[data-id="400000000000000009"]')->text());
    }

    public function testTheSearchMatchesUsernameAndDiscordId(): void
    {
        $byId = $this->client->request('GET', $this->crudUrl(Action::INDEX, query: ['query' => self::JUJU]));
        $this->assertCount(1, $byId->filter('tbody tr'));

        $juju = $this->getUser(self::JUJU);
        $byName = $this->client->request('GET', $this->crudUrl(Action::INDEX, query: ['query' => $juju->getUsername()]));
        $this->assertCount(1, $byName->filter(\sprintf('tr[data-id="%s"]', self::JUJU)));
    }

    public function testTheDetailLinksToTheWalletAndToItsFilteredTransactions(): void
    {
        $juju = $this->getUser(self::JUJU);

        $crawler = $this->client->request('GET', $this->crudUrl(Action::DETAIL, self::JUJU));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $juju->getUsername());
        $this->assertGreaterThan(0, $crawler->filter(\sprintf('a[href*="entityId=%s"]', $juju->getWallet()?->getId()))->count());

        $filtered = $this->client->click($crawler->filter('a.action-transactions')->link());

        self::assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $filtered->filter('tbody tr')->count());
        self::assertSelectorTextContains('tbody', (string) $juju->getWallet()?->getName());
    }

    public function testTheTransactionsActionIsHiddenWithoutWallet(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist((new DiscordUser())->setDiscordId('400000000000000009')->setUsername('Ghost'));
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->crudUrl(Action::DETAIL, '400000000000000009'));

        self::assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('a.action-transactions'));
    }

    public function testTheCrudIsReadOnly(): void
    {
        $crawler = $this->client->request('GET', $this->crudUrl(Action::INDEX));
        self::assertSelectorNotExists('.action-new');
        self::assertSelectorNotExists('.action-edit');
        self::assertSelectorNotExists('.action-delete');
        $this->assertCount(0, $crawler->filter('input[type="checkbox"].form-batch-checkbox'));

        foreach ([Action::NEW, Action::EDIT, Action::DELETE] as $action) {
            $this->client->request('GET', $this->crudUrl($action, self::JUJU));
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testANonAdminIsDenied(): void
    {
        $this->client->loginUser($this->getUser(self::JUJU));

        $this->client->request('GET', $this->crudUrl(Action::INDEX));

        self::assertResponseStatusCodeSame(403);
    }

    public function testTheListQueryCountDoesNotGrowWithTheNumberOfRows(): void
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $this->crudUrl(Action::INDEX));
        $before = $this->queryCount();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        foreach (['1', '2', '3'] as $suffix) {
            $user = (new DiscordUser())->setDiscordId('40000000000000010' . $suffix)->setUsername('Extra ' . $suffix);
            $entityManager->persist($user);
            $entityManager->persist((new Wallet())->setName('Extra ' . $suffix)->setType(WalletTypeEnum::USER)->setDiscordUser($user));
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
     * @param array<string, string> $query
     */
    private function crudUrl(string $action, ?string $entityId = null, array $query = []): string
    {
        $generator = static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(DiscordUserCrudController::class)
            ->setAction($action)
        ;

        foreach ($query as $key => $value) {
            $generator->set($key, $value);
        }

        if (null !== $entityId) {
            $generator->setEntityId($entityId);
        }

        return $generator->generateUrl();
    }

    private function getUser(string $discordId): DiscordUser
    {
        $user = static::getContainer()->get(DiscordUserRepository::class)->findOneBy(['discordId' => $discordId]);
        \assert(null !== $user);

        return $user;
    }
}
