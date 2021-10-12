<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Wallet;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as CustomAssert;

/**
 * @CustomAssert\Transaction
 */
class TransactionMessageDTO
{
    /**
     * @Assert\NotBlank(message="The amount value should not be blank.")
     * @CustomAssert\Amount()
     */
    private ?string $amount;

    /**
     * @Assert\NotNull(message="The walletFrom value should not be null.")
     * @Assert\Valid()
     */
    private ?Wallet $walletFrom;

    /**
     * @Assert\NotNull(message="The walletTo value should not be null.")
     * @Assert\Valid()
     */
    private ?Wallet $walletTo;

    public function __construct(
        ?string $amount,
        ?Wallet $walletFrom,
        ?Wallet $walletTo,
    ) {
        $this->amount = $amount;
        $this->walletFrom = $walletFrom;
        $this->walletTo = $walletTo;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getWalletFrom(): ?Wallet
    {
        return $this->walletFrom;
    }

    public function getWalletTo(): ?Wallet
    {
        return $this->walletTo;
    }
}
