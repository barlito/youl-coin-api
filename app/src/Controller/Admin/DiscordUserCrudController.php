<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DiscordUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use PHPMD\PHPMD;

class DiscordUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DiscordUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->renderContentMaximized();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('discordId'),
//            AssociationField::new('wallet'),
            Field::new('notes'),
        ];
    }
}