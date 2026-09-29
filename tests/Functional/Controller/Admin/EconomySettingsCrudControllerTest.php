<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\EconomySettingsCrudController;
use App\Entity\EconomySettings;
use App\Repository\DiscordUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class EconomySettingsCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->client->loginUser($this->getAdminUser());
    }

    public function testAnAdminCanChangeTheWelcomeBonusAmountInCoins(): void
    {
        $crawler = $this->client->request('GET', $this->adminUrl(Action::EDIT));
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="EconomySettings"]')->form([
            'EconomySettings[welcomeBonusAmountCoins]' => '500',
        ]));

        self::assertResponseRedirects();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $settings = $entityManager->find(EconomySettings::class, EconomySettings::SINGLETON_ID);

        $this->assertInstanceOf(EconomySettings::class, $settings);
        $this->assertSame('50000000000', $settings->getWelcomeBonusAmount());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outOfRangeAmounts(): iterable
    {
        yield 'negative' => ['-1'];
        yield 'above the 100 000 coins ceiling' => ['100001'];
    }

    #[DataProvider('outOfRangeAmounts')]
    public function testAnOutOfRangeWelcomeBonusIsRefused(string $coins): void
    {
        $crawler = $this->client->request('GET', $this->adminUrl(Action::EDIT));

        $this->client->submit($crawler->filter('form[name="EconomySettings"]')->form([
            'EconomySettings[welcomeBonusAmountCoins]' => $coins,
        ]));

        self::assertResponseStatusCodeSame(422);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $settings = $entityManager->find(EconomySettings::class, EconomySettings::SINGLETON_ID);

        $this->assertInstanceOf(EconomySettings::class, $settings);
        $this->assertSame(EconomySettings::DEFAULT_WELCOME_BONUS_AMOUNT, $settings->getWelcomeBonusAmount());
    }

    public function testTheCeilingItselfIsAccepted(): void
    {
        $crawler = $this->client->request('GET', $this->adminUrl(Action::EDIT));

        $this->client->submit($crawler->filter('form[name="EconomySettings"]')->form([
            'EconomySettings[welcomeBonusAmountCoins]' => '100000',
        ]));

        self::assertResponseRedirects();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $settings = $entityManager->find(EconomySettings::class, EconomySettings::SINGLETON_ID);

        $this->assertInstanceOf(EconomySettings::class, $settings);
        $this->assertSame('10000000000000', $settings->getWelcomeBonusAmount());
    }

    private function adminUrl(string $action): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(EconomySettingsCrudController::class)
            ->setEntityId(EconomySettings::SINGLETON_ID)
            ->setAction($action)
            ->generateUrl()
        ;
    }

    private function getAdminUser(): UserInterface
    {
        /** @var DiscordUserRepository $discordUserRepository */
        $discordUserRepository = static::getContainer()->get(DiscordUserRepository::class);
        $user = $discordUserRepository->findOneBy(['discordId' => '188967649332428800']);

        if (null === $user) {
            throw new \RuntimeException('Admin user not found');
        }

        return $user;
    }
}
