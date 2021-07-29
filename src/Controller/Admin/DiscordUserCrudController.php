<?php

namespace App\Controller\Admin;

use App\Entity\DiscordUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class DiscordUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DiscordUser::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
