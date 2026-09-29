<?php

declare(strict_types=1);

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ApiUser;
use App\Entity\Transaction;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * An API client only sees the transactions it created: another client's transaction is a 404.
 */
final readonly class IssuerScopedTransactionExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->scopeToIssuer($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->scopeToIssuer($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function scopeToIssuer(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass): void
    {
        if (Transaction::class !== $resourceClass) {
            return;
        }

        $apiUser = $this->security->getUser();
        $alias = $queryBuilder->getRootAliases()[0];

        if (!$apiUser instanceof ApiUser) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $parameter = $queryNameGenerator->generateParameterName('issuer');
        $queryBuilder
            ->andWhere(\sprintf('%s.issuer = :%s', $alias, $parameter))
            ->setParameter($parameter, $apiUser)
        ;
    }
}
