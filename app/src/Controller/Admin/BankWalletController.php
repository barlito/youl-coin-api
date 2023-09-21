<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Enum\RoleEnum;
use App\Form\Type\BankWalletTransactionType;
use App\Service\Handler\TransactionHandler;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(RoleEnum::ROLE_ADMIN->value)]
class BankWalletController extends AbstractController
{
    public function __construct(
        private readonly TransactionHandler $transactionHandler,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin/bank-wallet', name: 'admin_bank_wallet')]
    public function index(Request $request)
    {
        $form = $this->createForm(BankWalletTransactionType::class, new BankWalletTransactionDTO());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $bankWalletTransaction = $form->getData();
            $this->transactionHandler->handleBankTransaction($bankWalletTransaction);

            return $this->redirect(
                $this->adminUrlGenerator->setController(WalletCrudController::class)->setAction(Action::INDEX)->generateUrl(),
            );
        }

        return $this->render('admin/bank-wallet/index.html.twig', [
            'form' => $form,
        ]);
    }
}
