<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ApiUser;
use App\Enum\Roles\ApiUserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

/** @extends AbstractCrudController<ApiUser> */
class ApiUserCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

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
        $regenerateKey = Action::new('regenerateKey', 'Régénérer la clé')
            ->linkToCrudAction('regenerateKey')
            ->renderAsForm()
            ->askConfirmation('L\'ancienne clé cessera de fonctionner immédiatement. Continuer ?')
        ;

        // Transactions keep a reference to their API client: revoke a client by removing its roles instead
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $regenerateKey)
            ->add(Crud::PAGE_DETAIL, $regenerateKey)
            ->disable(Action::DELETE, Action::BATCH_DELETE)
        ;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id')->hideOnForm();
        yield TextField::new('name');
        yield TextField::new('displayName', 'Nom affiché aux joueurs')->setHelp('Ex. « Youl TCG ». Sinon le nom technique est affiché.');
        yield TextField::new('apiKeyPrefix', 'Préfixe de la clé')->hideOnForm();

        yield ChoiceField::new('roles')
            ->allowMultipleChoices()
            ->setChoices(array_column(ApiUserRoleEnum::cases(), 'value', 'name'))
            ->hideOnIndex()
        ;
    }

    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $plainApiKey = $this->generateApiKey();
        $entityInstance->setPlainApiKey($plainApiKey);

        parent::persistEntity($entityManager, $entityInstance);

        $this->flashApiKey($entityInstance, $plainApiKey);
    }

    /**
     * @param AdminContext<ApiUser> $context
     */
    public function regenerateKey(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $apiUser = $context->getEntity()->getInstance();
        \assert($apiUser instanceof ApiUser);
        $plainApiKey = $this->generateApiKey();
        $apiUser->setPlainApiKey($plainApiKey);
        $entityManager->flush();

        $this->flashApiKey($apiUser, $plainApiKey);

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    private function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    // Shown once: the plain key is not recoverable afterwards
    private function flashApiKey(ApiUser $apiUser, string $plainApiKey): void
    {
        $this->addFlash('success', \sprintf(
            'Clé API de <strong>%s</strong> (à copier maintenant, elle ne sera plus affichée) : <code>%s</code>',
            htmlspecialchars((string) $apiUser->getName()),
            $plainApiKey,
        ));
    }
}
