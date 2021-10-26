<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

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

    /**
     * @Assert\NotNull(message="The type value should not be null.")
     * @Assert\Choice(TransactionTypeEnum::VALUES)
     */
    private ?string $type;

    private ?string $message;

    public function __construct(
        ?string $amount,
        ?Wallet $walletFrom,
        ?Wallet $walletTo,
        ?string $type,
        ?string $message,
    ) {
        $this->amount = $amount;
        $this->walletFrom = $walletFrom;
        $this->walletTo = $walletTo;
        $this->type = $type;
        $this->message = $message;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
