<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;
use App\Repository\WalletRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Wallet>
 */
final readonly class BankWalletProvider implements ProviderInterface
{
    public function __construct(private WalletRepository $walletRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Wallet
    {
        return $this->walletRepository->findOneBy(['type' => WalletTypeEnum::BANK]) ?? throw new NotFoundHttpException('Bank wallet not found.');
    }
}
