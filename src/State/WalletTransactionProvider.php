<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\WalletTransactionView;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Repository\DiscordUserRepository;
use App\Repository\TransactionRepository;
use App\State\Pagination\WalletTransactionPaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<WalletTransactionView>
 */
final readonly class WalletTransactionProvider implements ProviderInterface
{
    public function __construct(
        private DiscordUserRepository $discordUserRepository,
        private TransactionRepository $transactionRepository,
        private Pagination $pagination,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WalletTransactionPaginator
    {
        $wallet = $this->discordUserRepository->find((string) ($uriVariables['discordId'] ?? ''))?->getWallet();

        if (!$wallet instanceof Wallet || null === $wallet->getId()) {
            throw new NotFoundHttpException('Player or wallet not found.');
        }

        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        [$transactions, $totalItems] = $this->transactionRepository->findWalletHistory($wallet, $offset, $limit);

        $views = array_map(
            static fn (Transaction $transaction): WalletTransactionView => WalletTransactionView::fromTransaction($transaction, $wallet),
            $transactions,
        );

        return new WalletTransactionPaginator($views, $page, $limit, $totalItems);
    }
}
