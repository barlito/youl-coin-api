<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionDirectionEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\WalletTypeEnum;
use App\Security\Voter\WalletHistoryVoter;
use App\State\WalletTransactionProvider;

// Read-only projection of a Transaction from one wallet's point of view: never externalIdentifier, issuer, reason or initiatedBy
#[ApiResource(operations: [
    new GetCollection(
        uriTemplate: '/user/{discordId}/transactions',
        uriVariables: ['discordId'],
        security: 'is_granted("' . WalletHistoryVoter::READ . '", discordId)',
        provider: WalletTransactionProvider::class,
    ),
], paginationClientItemsPerPage: false, paginationItemsPerPage: 30)]
final readonly class WalletTransactionView
{
    public function __construct(
        public string $id,
        public TransactionTypeEnum $type,
        public string $amount,
        public TransactionDirectionEnum $direction,
        public ?WalletTypeEnum $counterpartType,
        public ?string $counterpartDiscordId,
        public \DateTimeInterface $createdAt,
    ) {
    }

    // MINT/BURN never reach here in practice (they never reference a player wallet), tolerated defensively
    public static function fromTransaction(Transaction $transaction, Wallet $viewerWallet): self
    {
        $isOutgoing = $transaction->getWalletFrom() === $viewerWallet;
        $counterpart = $isOutgoing ? $transaction->getWalletTo() : $transaction->getWalletFrom();

        return new self(
            id: (string) $transaction->getId(),
            type: $transaction->getType() ?? throw new \LogicException('Transaction without a type cannot be viewed.'),
            amount: (string) $transaction->getAmount(),
            direction: $isOutgoing ? TransactionDirectionEnum::OUT : TransactionDirectionEnum::IN,
            counterpartType: $counterpart?->getType(),
            counterpartDiscordId: WalletTypeEnum::USER === $counterpart?->getType() ? $counterpart->getDiscordUser()?->getDiscordId() : null,
            createdAt: $transaction->getCreatedAt() ?? throw new \LogicException('Transaction without a createdAt cannot be viewed.'),
        );
    }
}
