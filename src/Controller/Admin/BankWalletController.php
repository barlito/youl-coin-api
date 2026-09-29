<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Roles\RoleEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Form\Type\MintBurnTransactionType;
use App\Form\Type\TransactionType;
use App\Repository\WalletRepository;
use App\Service\Handler\TransactionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(RoleEnum::ROLE_ADMIN->value)]
class BankWalletController extends AbstractController
{
    public function __construct(
        private readonly TransactionHandler $transactionHandler,
        private readonly WalletRepository $walletRepository,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/admin/bank-wallet', name: 'admin_bank_wallet')]
    public function index(Request $request): Response
    {
        $transferForm = $this->createForm(TransactionType::class, new Transaction());
        $transferForm->handleRequest($request);
        if ($transferForm->isSubmitted() && $transferForm->isValid()) {
            $this->submitTransfer($transferForm);
        }

        $mintForm = $this->formFactory->createNamed('mint', MintBurnTransactionType::class, new Transaction(), ['submit_label' => 'Mint']);
        $mintForm->handleRequest($request);
        if ($mintForm->isSubmitted() && $mintForm->isValid()) {
            $this->submitMintOrBurn($mintForm, TransactionTypeEnum::MINT);
        }

        $burnForm = $this->formFactory->createNamed('burn', MintBurnTransactionType::class, new Transaction(), ['submit_label' => 'Burn']);
        $burnForm->handleRequest($request);
        if ($burnForm->isSubmitted() && $burnForm->isValid()) {
            $this->submitMintOrBurn($burnForm, TransactionTypeEnum::BURN);
        }

        return $this->render('admin/bank-wallet/index.html.twig', [
            'form' => $transferForm,
            'mintForm' => $mintForm,
            'burnForm' => $burnForm,
        ]);
    }

    /**
     * @param FormInterface<Transaction> $form
     */
    private function submitTransfer(FormInterface $form): void
    {
        $transaction = $form->getData();
        $transaction->setAmount($transaction->getAmount() . '00000000');

        try {
            $this->transactionHandler->handleTransaction($transaction);
        } catch (\Exception $exception) {
            $form->addError(new FormError($exception->getMessage()));
        }
    }

    /**
     * @param FormInterface<Transaction> $form
     */
    private function submitMintOrBurn(FormInterface $form, TransactionTypeEnum $type): void
    {
        $bankWallet = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        if (!$bankWallet instanceof Wallet) {
            $form->addError(new FormError('No Bank Wallet exists.'));

            return;
        }

        $transaction = $form->getData();
        $transaction
            ->setAmount($transaction->getAmount() . '00000000')
            ->setType($type)
        ;

        if (TransactionTypeEnum::MINT === $type) {
            $transaction->setWalletTo($bankWallet);
        } else {
            $transaction->setWalletFrom($bankWallet);
        }

        $admin = $this->getUser();
        if ($admin instanceof DiscordUser) {
            $transaction->setInitiatedBy($admin);
        }

        try {
            $this->transactionHandler->handleTransaction($transaction);
        } catch (\Exception $exception) {
            $form->addError(new FormError($exception->getMessage()));
        }
    }
}
