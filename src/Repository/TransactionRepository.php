<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     * Transactions where the wallet is source or destination, most recent first.
     *
     * @return array{0: Transaction[], 1: int}
     */
    public function findWalletHistory(Wallet $wallet, int $offset, int $limit): array
    {
        // Binding the Wallet object skips the custom "ulid" type conversion (ORM's parameter type inference
        // only handles scalars): the base32 id would reach Postgres unconverted into the uuid column.
        $queryBuilder = $this->createQueryBuilder('t')
            ->andWhere('t.walletFrom = :walletFrom OR t.walletTo = :walletTo')
            ->setParameter('walletFrom', $wallet->getId(), 'ulid')
            ->setParameter('walletTo', $wallet->getId(), 'ulid')
            // createdAt has second precision: id as tiebreak keeps pagination stable across pages
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        $paginator = new DoctrinePaginator($queryBuilder, fetchJoinCollection: false);

        return [iterator_to_array($paginator, false), $paginator->count()];
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
