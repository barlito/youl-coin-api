<?php

declare(strict_types=1);

namespace App\Message;

use App\Enum\TransactionTypeEnum;
use App\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class TransactionMessage
{
    public function __construct(
        #[Assert\NotBlank(message: 'The amount value should not be blank.')]
        #[CustomAssert\Entity\Transaction\Amount]
        private readonly ?string $amount = null,
        #[Assert\NotBlank(message: 'The discordUserIdFrom value should not be blank.')]
        #[CustomAssert\Entity\Wallet\DiscordUserWalletExist]
        private readonly ?string $discordUserIdFrom = null,
        #[Assert\NotBlank(message: 'The discordUserIdTo value should not be blank.')]
        #[CustomAssert\Entity\Wallet\DiscordUserWalletExist]
        private readonly ?string $discordUserIdTo = null,
        #[Assert\NotNull(message: 'The type value you selected is not a valid Transaction Type or is null.')]
        private readonly ?TransactionTypeEnum $type = null,
        private readonly ?string $externalIdentifier = null,
    ) {
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getDiscordUserIdFrom(): ?string
    {
        return $this->discordUserIdFrom;
    }

    public function getDiscordUserIdTo(): ?string
    {
        return $this->discordUserIdTo;
    }

    public function getType(): ?TransactionTypeEnum
    {
        return $this->type;
    }

    public function getExternalIdentifier(): ?string
    {
        return $this->externalIdentifier;
    }
}
