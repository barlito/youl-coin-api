<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AllowedDiscordUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<AllowedDiscordUser>
 */
class AllowedDiscordUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AllowedDiscordUser::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Allowed Discord user')
            ->setEntityLabelInPlural('Discord whitelist')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->renderContentMaximized()
        ;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('discordId')
            ->setHelp('The Discord user ID (right click on the user > "Copy User ID", developer mode required).')
            ->setFormTypeOption('disabled', Crud::PAGE_EDIT === $pageName)
        ;
        yield TextField::new('label')
            ->setHelp('Free label to remember who this ID belongs to.')
        ;
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
