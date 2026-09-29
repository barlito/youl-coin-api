<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EconomySettings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

// Singleton settings row: no create/delete, only viewing and editing the one row
/**
 * @extends AbstractCrudController<EconomySettings>
 */
class EconomySettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EconomySettings::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Economy Settings')
            ->setEntityLabelInPlural('Economy Settings')
        ;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::DETAIL, Action::BATCH_DELETE);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('welcomeBonusAmountCoins', 'Welcome bonus (in coins, 0 disables it)');
        yield DateTimeField::new('welcomeBonusSince', 'Welcome bonus granted to wallets created since (UTC)')->setFormTypeOption('disabled', true);
    }
}
