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
use App\Service\Util\MoneyUtil;
use Brick\Math\Exception\MathException;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
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
        private readonly MoneyUtil $moneyUtil,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin/bank-wallet', name: 'admin_bank_wallet')]
    public function index(Request $request): Response
    {
        $transferForm = $this->createForm(TransactionType::class, new Transaction());
        $transferForm->handleRequest($request);
        if ($transferForm->isSubmitted() && $transferForm->isValid() && $this->submitTransfer($transferForm)) {
            return $this->redirectToBankWallet();
        }

        $mintForm = $this->formFactory->createNamed('mint', MintBurnTransactionType::class, new Transaction(), ['submit_label' => 'Mint']);
        $mintForm->handleRequest($request);
        if ($mintForm->isSubmitted() && $mintForm->isValid() && $this->submitMintOrBurn($mintForm, TransactionTypeEnum::MINT)) {
            return $this->redirectToBankWallet();
        }

        $burnForm = $this->formFactory->createNamed('burn', MintBurnTransactionType::class, new Transaction(), ['submit_label' => 'Burn']);
        $burnForm->handleRequest($request);
        if ($burnForm->isSubmitted() && $burnForm->isValid() && $this->submitMintOrBurn($burnForm, TransactionTypeEnum::BURN)) {
            return $this->redirectToBankWallet();
        }

        return $this->render('admin/bank-wallet/index.html.twig', [
            'form' => $transferForm,
            'mintForm' => $mintForm,
            'burnForm' => $burnForm,
        ]);
    }

    // Post/Redirect/Get: a refresh must never replay a Mint, Burn or transfer
    private function redirectToBankWallet(): Response
    {
        return $this->redirect($this->adminUrlGenerator->setRoute('admin_bank_wallet')->generateUrl());
    }

    /**
     * @param FormInterface<Transaction> $form
     */
    private function submitTransfer(FormInterface $form): bool
    {
        $transaction = $form->getData();
        $amount = $this->convertAmount($form, $transaction->getAmount());
        if (null === $amount) {
            return false;
        }

        $transaction->setAmount($amount);

        return $this->handle($form, $transaction, 'Virement de %s effectué.');
    }

    /**
     * @param FormInterface<Transaction> $form
     */
    private function submitMintOrBurn(FormInterface $form, TransactionTypeEnum $type): bool
    {
        $bankWallet = $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]);
        if (!$bankWallet instanceof Wallet) {
            $form->addError(new FormError('No Bank Wallet exists.'));

            return false;
        }

        $admin = $this->getUser();
        if (!$admin instanceof DiscordUser) {
            $form->addError(new FormError('Seul un administrateur connecté via Discord peut créer ou détruire des coins.'));

            return false;
        }

        $transaction = $form->getData();
        $amount = $this->convertAmount($form, $transaction->getAmount());
        if (null === $amount) {
            return false;
        }

        $transaction
            ->setAmount($amount)
            ->setType($type)
            ->setInitiatedBy($admin)
        ;

        if (TransactionTypeEnum::MINT === $type) {
            $transaction->setWalletTo($bankWallet);
        } else {
            $transaction->setWalletFrom($bankWallet);
        }

        return $this->handle($form, $transaction, TransactionTypeEnum::MINT === $type ? 'Mint de %s effectué.' : 'Burn de %s effectué.');
    }

    /**
     * @param FormInterface<Transaction> $form
     */
    private function handle(FormInterface $form, Transaction $transaction, string $successMessage): bool
    {
        try {
            $this->transactionHandler->handleTransaction($transaction);
        } catch (\Exception $exception) {
            $form->addError(new FormError($exception->getMessage()));

            return false;
        }

        $this->addFlash('success', \sprintf($successMessage, $this->moneyUtil->getFormattedMoney((string) $transaction->getAmount())));

        return true;
    }

    /**
     * @param FormInterface<Transaction> $form
     *
     * @return numeric-string|null
     */
    private function convertAmount(FormInterface $form, ?string $coins): ?string
    {
        try {
            $minor = $this->moneyUtil->coinsToMinor((string) $coins);
        } catch (MathException) {
            $minor = null;
        }

        if (null === $minor || !is_numeric($minor) || bccomp($minor, '0') <= 0) {
            $form->addError(new FormError('Montant invalide : un nombre de coins positif, 8 décimales au plus.'));

            return null;
        }

        return $minor;
    }
}
