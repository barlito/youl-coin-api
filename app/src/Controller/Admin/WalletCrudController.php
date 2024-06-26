<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Service\Util\MoneyUtil;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WalletCrudController extends AbstractCrudController
{
    public function __construct(private readonly MoneyUtil $moneyUtil)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Wallet::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setFormOptions([
                'validation_groups' => ['Default', 'wallet:create'],
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')->onlyOnDetail();

        yield IntegerField::new('amount')->formatValue(function ($value, $entity) {
            if (!$entity instanceof Wallet) {
                throw new UnexpectedTypeException($entity, Wallet::class);
            }

            return $this->moneyUtil->getFormattedMoney($entity->getAmount());
        });

        yield AssociationField::new('discordUser')->setQueryBuilder(
            fn (QueryBuilder $queryBuilder) => $queryBuilder->leftJoin('entity.wallet', 'w')
                ->where('w.id IS NULL'),
        );
        yield ChoiceField::new('type')
            ->setFormType(EnumType::class)
            ->setFormTypeOption('class', WalletTypeEnum::class)
        ;
        yield Field::new('name');
    }
}
