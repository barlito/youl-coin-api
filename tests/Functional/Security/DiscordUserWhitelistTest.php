<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\AllowedDiscordUser;
use App\Security\DiscordUserWhitelist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DiscordUserWhitelistTest extends KernelTestCase
{
    private DiscordUserWhitelist $discordUserWhitelist;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        system('bin/console hautelook:fixtures:load -n --env="test"');

        self::bootKernel();

        $this->discordUserWhitelist = static::getContainer()->get(DiscordUserWhitelist::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUserFromTheBootstrapParameterIsAllowed(): void
    {
        $this->assertTrue($this->discordUserWhitelist->isAllowed('188967649332428800'));
    }

    public function testUserFromTheDatabaseIsAllowed(): void
    {
        $this->assertTrue($this->discordUserWhitelist->isAllowed('297453953120075778'));
    }

    public function testUnknownUserIsNotAllowed(): void
    {
        $this->assertFalse($this->discordUserWhitelist->isAllowed('999999999999999999'));
    }

    public function testUserBecomesAllowedOnceAddedToTheDatabase(): void
    {
        $discordId = '123456789012345678';
        $this->assertFalse($this->discordUserWhitelist->isAllowed($discordId));

        $this->entityManager->persist(new AllowedDiscordUser()->setDiscordId($discordId)->setLabel('Newcomer'));
        $this->entityManager->flush();

        $this->assertTrue($this->discordUserWhitelist->isAllowed($discordId));
    }
}
