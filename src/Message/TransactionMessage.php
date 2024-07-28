<?php

declare(strict_types=1);

namespace App\Message;

use App\Enum\TransactionTypeEnum;
use App\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class TransactionMessage
{
    #[Assert\NotBlank(message: 'The amount value should not be blank.')]
    #[CustomAssert\Entity\Transaction\Amount]
    private ?string $amount;

    #[Assert\NotBlank(message: 'The discordUserIdFrom value should not be blank.')]
    #[CustomAssert\Entity\Wallet\DiscordUserWalletExist]
    private ?string $discordUserIdFrom;

    #[Assert\NotBlank(message: 'The discordUserIdTo value should not be blank.')]
    #[CustomAssert\Entity\Wallet\DiscordUserWalletExist]
    private ?string $discordUserIdTo;

    #[Assert\NotNull(message: 'The type value you selected is not a valid Transaction Type or is null.')]
    private ?TransactionTypeEnum $type;

    private ?string $externalIdentifier;

    public function __construct(
        string $amount = null,
        string $discordUserIdFrom = null,
        string $discordUserIdTo = null,
        TransactionTypeEnum $type = null,
        string $externalIdentifier = null,
    ) {
        $this->amount = $amount;
        $this->discordUserIdFrom = $discordUserIdFrom;
        $this->discordUserIdTo = $discordUserIdTo;
        $this->type = $type;
        $this->externalIdentifier = $externalIdentifier;
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
