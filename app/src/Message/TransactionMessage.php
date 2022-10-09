<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use App\Validator as CustomAssert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @CustomAssert\Transaction
 */
class TransactionMessage
{
    #[Groups('test')]
    #[Assert\NotBlank(message: 'The amount value should not be blank.')]
    #[CustomAssert\Amount]
    private ?string $amount;

    #[Groups('test')]
    #[Assert\NotNull(message: 'The walletFrom value should not be null.')]
    #[Assert\Valid]
    private ?Wallet $walletFrom;

    #[Groups('test')]
    #[Assert\NotNull(message: 'The walletTo value should not be null.')]
    #[Assert\Valid]
    private ?Wallet $walletTo;

    #[Groups('test')]
    #[Assert\NotNull(message: 'The type value should not be null.')]
    #[Assert\Choice(choices: TransactionTypeEnum::VALUES, message: 'The type value you selected is not a valid choice.')]
    private ?string $type;

    #[Groups('test')]
    private ?string $message;

    public function __construct(
        ?string $amount = null,
        ?Wallet $walletFrom = null,
        ?Wallet $walletTo = null,
        ?string $type = null,
        ?string $message = null,
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
