<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Filter\WalletFilter;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use App\Service\Util\MoneyUtil;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

/** @extends AbstractCrudController<Transaction> */
class TransactionCrudController extends AbstractCrudController
{
    public const string WALLET_FILTER = 'wallet';

    private const string EMPTY_VALUE = '—';

    public function __construct(private readonly MoneyUtil $moneyUtil)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setEntityLabelInSingular('Transaction')
            ->setEntityLabelInPlural('Transactions')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setTimezone('Europe/Paris')
            ->setDateTimeFormat('dd/MM/yyyy HH:mm:ss')
        ;
    }

    // The ledger is append-only: transactions only ever come from TransactionHandler
    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
        ;
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices($this->typeChoices()))
            ->add(DateTimeFilter::new('createdAt', 'Date'))
            ->add(EntityFilter::new('issuer', 'Client API'))
            ->add(EntityFilter::new('initiatedBy', 'Admin'))
            ->add(WalletFilter::new(self::WALLET_FILTER, 'Wallet (envoi ou réception)'))
        ;
    }

    // Fetch-join every association shown in the list: no query per row
    #[\Override]
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.walletFrom', 'walletFrom')->addSelect('walletFrom')
            ->leftJoin('entity.walletTo', 'walletTo')->addSelect('walletTo')
            ->leftJoin('entity.issuer', 'issuer')->addSelect('issuer')
            ->leftJoin('entity.initiatedBy', 'initiatedBy')->addSelect('initiatedBy')
            // Inverse one-to-one: Doctrine loads it eagerly, one query per admin otherwise
            ->leftJoin('initiatedBy.wallet', 'initiatedByWallet')->addSelect('initiatedByWallet')
        ;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'Date');

        yield ChoiceField::new('type')->setChoices($this->typeChoices())->renderAsBadges();

        yield TextField::new('amount', 'Montant')
            ->formatValue(fn (?string $value): string => null === $value ? self::EMPTY_VALUE : $this->moneyUtil->getFormattedMoney($value))
        ;
        yield AssociationField::new('walletFrom', 'Wallet source')->formatValue($this->orEmpty(...));
        yield AssociationField::new('walletTo', 'Wallet destination')->formatValue($this->orEmpty(...));
        yield AssociationField::new('issuer', 'Client API')->formatValue($this->orEmpty(...));
        yield AssociationField::new('initiatedBy', 'Admin')->formatValue($this->orEmpty(...));

        yield TextareaField::new('reason', 'Motif')->onlyOnDetail();
        yield TextField::new('externalIdentifier', 'Identifiant externe')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->onlyOnDetail();
    }

    /**
     * @return array<string, string>
     */
    private function typeChoices(): array
    {
        return array_column(
            array_map(static fn (TransactionTypeEnum $type): array => [$type->getLabel(), $type->value], TransactionTypeEnum::cases()),
            1,
            0,
        );
    }

    private function orEmpty(mixed $value): mixed
    {
        return $value ?? self::EMPTY_VALUE;
    }
}
