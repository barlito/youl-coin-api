<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Repository\AllowedDiscordUserRepository;
use App\Security\DiscordUserWhitelist;
use App\Service\Util\MoneyUtil;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/** @extends AbstractCrudController<DiscordUser> */
class DiscordUserCrudController extends AbstractCrudController
{
    private const string EMPTY_VALUE = '—';

    /** @var list<string>|null */
    private ?array $adminWhitelistedIds = null;

    public function __construct(
        private readonly MoneyUtil $moneyUtil,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly DiscordUserWhitelist $whitelist,
        private readonly AllowedDiscordUserRepository $allowedDiscordUserRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return DiscordUser::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setEntityLabelInSingular('Joueur')
            ->setEntityLabelInPlural('Joueurs')
            ->setDefaultSort(['username' => 'ASC'])
            ->setSearchFields(['username', 'discordId'])
        ;
    }

    // Players are created by the login flow and roles come from the DB or the JWT: read-only here
    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $transactions = Action::new('transactions', 'Transactions')
            ->linkToUrl(fn (DiscordUser $user): string => $this->adminUrlGenerator
                ->setController(TransactionCrudController::class)
                ->setAction(Action::INDEX)
                ->set('filters', [TransactionCrudController::WALLET_FILTER => ['comparison' => '=', 'value' => $user->getWallet()?->getId()]])
                ->generateUrl())
            ->displayIf(static fn (DiscordUser $user): bool => $user->getWallet() instanceof Wallet)
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $transactions)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
        ;
    }

    // Fetch-join the wallet shown in the list: the inverse one-to-one would cost one query per row
    #[\Override]
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.wallet', 'wallet')->addSelect('wallet')
        ;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username', 'Pseudo');
        yield TextField::new('discordId', 'Identifiant Discord');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices(array_combine(array_column(RoleEnum::cases(), 'value'), array_column(RoleEnum::cases(), 'value')))
            ->allowMultipleChoices()
            ->renderAsBadges([RoleEnum::ROLE_ADMIN->value => 'danger'])
        ;
        yield AssociationField::new('wallet', 'Solde')
            ->formatValue(fn (?Wallet $wallet): string => $wallet instanceof Wallet ? $this->moneyUtil->getFormattedMoney($wallet->getAmount()) : self::EMPTY_VALUE)
        ;
        yield TextField::new('discordId', 'Whitelist')
            ->setSortable(false)
            ->formatValue(fn (string $discordId): string => $this->whitelistStatus($discordId))
        ;
        yield AssociationField::new('wallet', 'Wallet')->onlyOnDetail();
    }

    private function whitelistStatus(string $discordId): string
    {
        if ($this->whitelist->isBootstrap($discordId)) {
            return 'Oui (config)';
        }

        $this->adminWhitelistedIds ??= $this->allowedDiscordUserRepository->findAllDiscordIds();

        return \in_array($discordId, $this->adminWhitelistedIds, true) ? 'Oui (admin)' : 'Non — révoqué';
    }
}
