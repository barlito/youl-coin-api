<?php

declare(strict_types=1);

namespace App\Admin\Filter;

use App\Entity\Wallet;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

// Transactions sent or received by a wallet; the "wallet" key is virtual, the filter targets both walletFrom and walletTo
final class WalletFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, string $label): self
    {
        return new self()
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle')
            // Only "is" makes sense: hide the comparison selector
            ->setFormTypeOption('comparison_type', HiddenType::class)
            ->setFormTypeOption('comparison_type_options', ['empty_data' => ComparisonType::EQ])
            ->setFormTypeOption('value_type_options', ['class' => Wallet::class])
        ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $wallet = $filterDataDto->getValue();
        if (!$wallet instanceof Wallet) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $parameterName = $filterDataDto->getParameterName();

        $queryBuilder
            ->andWhere(\sprintf('%1$s.walletFrom = :%2$s OR %1$s.walletTo = :%2$s', $alias, $parameterName))
            // Wallet ids are ULIDs: the entity itself would not be converted to the uuid column
            ->setParameter($parameterName, $wallet->getId(), 'ulid')
        ;
    }
}
