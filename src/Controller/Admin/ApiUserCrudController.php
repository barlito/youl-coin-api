<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ApiUser;
use App\Enum\Roles\ApiUserRoleEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApiUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApiUser::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
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
        yield TextField::new('id');
        yield TextField::new('name');
        yield TextField::new('apiKey')->formatValue(function ($value, $entity): string {
            if (!$entity instanceof ApiUser) {
                throw new UnexpectedTypeException($entity, ApiUser::class);
            }

            return substr((string) $entity->getApiKey(), 0, 5) . '...' . substr((string) $entity->getApiKey(), -5);
        });

        yield ChoiceField::new('roles')
            ->allowMultipleChoices()
            ->setChoices(ApiUserRoleEnum::cases())
            ->hideOnIndex()
        ;
    }
}
