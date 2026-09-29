<?php

declare(strict_types=1);

namespace App\Service\Wallet;

use App\Entity\DiscordUser;
use App\Entity\Wallet;
use App\Enum\WalletTypeEnum;

class UserWalletFactory
{
    // Not persisted: callers decide when (and how safely) to flush
    public function create(DiscordUser $user): Wallet
    {
        $wallet = new Wallet()
            ->setAmount('0')
            ->setType(WalletTypeEnum::USER)
            ->setName('Wallet ' . $user->getUsername())
        ;
        $user->setWallet($wallet);

        return $wallet;
    }
}
