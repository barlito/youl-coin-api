<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Repository\DiscordUserRepository;
use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthContext extends WebTestCase implements Context
{
    /**
     * @Given /^an admin user is logged in$/
     */
    public function anAdminUserIsLoggedIn(): void
    {
        self::createClient()->loginUser($this->getAdminUser());
    }

    private function getAdminUser(): UserInterface
    {
        /** @var DiscordUserRepository $discordUserRepository */
        $discordUserRepository = static::getContainer()->get(DiscordUserRepository::class);
        $user = $discordUserRepository->findOneBy(['discordId' => '188967649332428800']);

        if (null === $user) {
            throw new \RuntimeException('User not found');
        }

        return $user;
    }
}
