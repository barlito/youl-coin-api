<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Repository\DiscordUserRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class BankWalletControllerTest extends WebTestCase
{
    private const string BANK_WALLET_ID = '01HAJGPGCP28GFA6QD08NMH764';

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        $this->client = static::createClient();
        $this->client->loginUser($this->getAdminUser());
    }

    public function testAnAdminCanMintCoinsToTheBank(): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="mint"]')->form([
            'mint[amount]' => '5',
            'mint[reason]' => 'Genesis top-up',
        ]));

        self::assertResponseRedirects();
        self::assertResponseHeaderSame('Location', 'http://localhost/admin?routeName=admin_bank_wallet');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $bankWallet = $entityManager->find(Wallet::class, self::BANK_WALLET_ID);
        $this->assertNotNull($bankWallet);
        $this->assertSame('1000500000000', $bankWallet->getAmount());

        $mint = static::getContainer()->get(TransactionRepository::class)->findOneBy(['type' => TransactionTypeEnum::MINT]);
        $this->assertInstanceOf(Transaction::class, $mint);
        $this->assertNull($mint->getWalletFrom());
        $this->assertSame(self::BANK_WALLET_ID, $mint->getWalletTo()?->getId());
        $this->assertSame('Genesis top-up', $mint->getReason());
        $this->assertSame('188967649332428800', $mint->getInitiatedBy()?->getDiscordId());
    }

    public function testAnAdminCanBurnCoinsFromTheBank(): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="burn"]')->form([
            'burn[amount]' => '5',
            'burn[reason]' => 'Destroying unused coins',
        ]));

        self::assertResponseRedirects();
        self::assertResponseHeaderSame('Location', 'http://localhost/admin?routeName=admin_bank_wallet');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $bankWallet = $entityManager->find(Wallet::class, self::BANK_WALLET_ID);
        $this->assertNotNull($bankWallet);
        $this->assertSame('999500000000', $bankWallet->getAmount());

        $burn = static::getContainer()->get(TransactionRepository::class)->findOneBy(['type' => TransactionTypeEnum::BURN]);
        $this->assertInstanceOf(Transaction::class, $burn);
        $this->assertNull($burn->getWalletTo());
        $this->assertSame(self::BANK_WALLET_ID, $burn->getWalletFrom()?->getId());
        $this->assertSame('Destroying unused coins', $burn->getReason());
    }

    public function testARefreshAfterASuccessfulMintDoesNotMintTwice(): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());
        $this->client->submit($crawler->filter('form[name="mint"]')->form([
            'mint[amount]' => '5',
            'mint[reason]' => 'Genesis top-up',
        ]));
        self::assertResponseRedirects();
        self::assertResponseHeaderSame('Location', 'http://localhost/admin?routeName=admin_bank_wallet');

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Mint de');

        $this->client->request('GET', $this->bankWalletUrl());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('body', 'Mint de');

        $this->assertCount(1, static::getContainer()->get(TransactionRepository::class)->findBy(['type' => TransactionTypeEnum::MINT]));
        $this->assertSame('1000500000000', $this->bankAmount());
    }

    public function testAMintAcceptsADecimalAmountOfCoins(): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());
        $this->client->submit($crawler->filter('form[name="mint"]')->form([
            'mint[amount]' => '0.5',
            'mint[reason]' => 'Half a coin',
        ]));

        self::assertResponseRedirects();
        self::assertResponseHeaderSame('Location', 'http://localhost/admin?routeName=admin_bank_wallet');
        $this->assertSame('1000050000000', $this->bankAmount());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAmounts(): iterable
    {
        yield 'too many decimals' => ['0.000000001'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
        yield 'not a number' => ['abc'];
    }

    #[DataProvider('invalidAmounts')]
    public function testAnInvalidAmountIsRejectedWithAReadableMessage(string $amount): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());
        $this->client->submit($crawler->filter('form[name="mint"]')->form([
            'mint[amount]' => $amount,
            'mint[reason]' => 'Invalid amount',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Montant invalide');
        $this->assertNull(static::getContainer()->get(TransactionRepository::class)->findOneBy(['type' => TransactionTypeEnum::MINT]));
    }

    public function testAMintWithATooShortReasonIsRejected(): void
    {
        $crawler = $this->client->request('GET', $this->bankWalletUrl());

        $this->client->submit($crawler->filter('form[name="mint"]')->form([
            'mint[amount]' => '5',
            'mint[reason]' => 'x',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'reason');

        $mint = static::getContainer()->get(TransactionRepository::class)->findOneBy(['type' => TransactionTypeEnum::MINT]);
        $this->assertNull($mint);
    }

    private function bankAmount(): string
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return (string) $entityManager->find(Wallet::class, self::BANK_WALLET_ID)?->getAmount();
    }

    // The route only carries the EasyAdmin layout context (ea()) when reached through the dashboard proxy
    private function bankWalletUrl(): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setRoute('admin_bank_wallet')
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
