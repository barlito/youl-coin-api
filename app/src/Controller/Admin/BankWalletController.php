<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Enum\RoleEnum;
use App\Form\Type\BankWalletTransactionType;
use App\Form\Type\TransactionType;
use App\Service\Handler\TransactionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(RoleEnum::ROLE_ADMIN->value)]
class BankWalletController extends AbstractController
{
    public function __construct(
        private readonly TransactionHandler $transactionHandler,
    ) {
    }

    #[Route('/admin/bank-wallet', name: 'admin_bank_wallet')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(TransactionType::class, new Transaction());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form->getData()->setAmount($form->getData()->getAmount() . '00000000');

            try {
                $this->transactionHandler->handleTransaction($form->getData());
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('admin/bank-wallet/index.html.twig', [
            'form' => $form,
        ]);
    }
}
