<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscordUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscordUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordUser[]    findAll()
 * @method DiscordUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<DiscordUser>
 */
class DiscordUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordUser::class);
    }

    /**
     * @param string[] $discordIds
     *
     * @return array<string, string> username by discord id
     */
    public function findUsernamesByDiscordIds(array $discordIds): array
    {
        if ([] === $discordIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.discordId', 'u.username')
            ->andWhere('u.discordId IN (:ids)')
            ->setParameter('ids', $discordIds)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_column($rows, 'username', 'discordId');
    }

    // /**
    //  * @return DiscordUser[] Returns an array of DiscordUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DiscordUser
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
