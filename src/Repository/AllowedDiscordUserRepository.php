<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AllowedDiscordUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AllowedDiscordUser>
 */
class AllowedDiscordUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AllowedDiscordUser::class);
    }

    public function existsByDiscordId(string $discordId): bool
    {
        return null !== $this->find($discordId);
    }

    /**
     * @return list<string>
     */
    public function findAllDiscordIds(): array
    {
        return array_values(array_map(strval(...), $this->createQueryBuilder('a')->select('a.discordId')->getQuery()->getSingleColumnResult()));
    }
}
