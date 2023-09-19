<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Enum\RoleEnum;
use App\Form\Type\BankWalletTransactionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(RoleEnum::ROLE_ADMIN->value)]
class BankWalletController extends AbstractController
{
    #[Route('/admin/bank-wallet', name: 'admin_bank_wallet')]
    public function index(Request $request)
    {
        $form = $this->createForm(BankWalletTransactionType::class, new BankWalletTransactionDTO());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $bankWalletTransaction = $form->getData();

        }

        return $this->render('admin/bank-wallet/index.html.twig', [
            'form' => $form,
        ]);
    }
}
