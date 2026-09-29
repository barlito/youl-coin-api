<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    // Transactions where the wallet is source or destination, most recent first
    public function findWalletHistory(Wallet $wallet, int $offset, int $limit): WalletHistoryPage
    {
        // The ulid type must be given explicitly: parameter type inference does not reach the custom type
        $queryBuilder = $this->createQueryBuilder('t')
            ->andWhere('t.walletFrom = :wallet OR t.walletTo = :wallet')
            ->setParameter('wallet', $wallet->getId(), 'ulid')
            // createdAt has second precision: id as tiebreak keeps pagination stable across pages
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        $paginator = new DoctrinePaginator($queryBuilder, fetchJoinCollection: false);

        return new WalletHistoryPage(iterator_to_array($paginator, false), $paginator->count());
    }

    /**
     * Total received and sent by the wallet since a moment, in minor units
     *
     * @return array{received: numeric-string, sent: numeric-string}
     */
    public function sumFlowSince(Wallet $wallet, \DateTimeImmutable $since): array
    {
        $flow = $this->getEntityManager()->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    COALESCE(SUM(amount::numeric) FILTER (WHERE wallet_to_id = :wallet), 0) AS received,
                    COALESCE(SUM(amount::numeric) FILTER (WHERE wallet_from_id = :wallet), 0) AS sent
                FROM transaction
                WHERE created_at >= :since AND (wallet_from_id = :wallet OR wallet_to_id = :wallet)
                SQL,
            [
                'wallet' => Ulid::fromString((string) $wallet->getId())->toRfc4122(),
                // created_at is stored in UTC
                'since' => $since->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            ],
            ['wallet' => ParameterType::STRING, 'since' => ParameterType::STRING],
        );

        /** @var array{received: numeric-string, sent: numeric-string} $flow */
        return $flow;
    }

    // /**
    //  * @return Transaction[] Returns an array of Transaction objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
