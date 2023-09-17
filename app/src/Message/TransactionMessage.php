<?php

declare(strict_types=1);

namespace App\Message;

use App\Enum\TransactionTypeEnum;
use App\Validator as CustomAssert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class TransactionMessage
{
    #[Groups('log')]
    #[Assert\NotBlank(message: 'The amount value should not be blank.')]
    #[CustomAssert\Entity\Transaction\Amount]
    private ?string $amount;

    #[Groups('log')]
    #[Assert\NotBlank(message: 'The discordUserIdFrom value should not be blank.')]
    private ?string $discordUserIdFrom;

    #[Groups('log')]
    #[Assert\NotBlank(message: 'The discordUserIdTo value should not be blank.')]
    private ?string $discordUserIdTo;

    #[Groups('log')]
    #[Assert\NotNull(message: 'The type value should not be null.')]
    #[Assert\Choice(choices: TransactionTypeEnum::VALUES, message: 'The type value you selected is not a valid choice.')]
    private ?string $type;

    #[Groups('log')]
    private ?string $message;

    public function __construct(
        ?string $amount = null,
        ?string $discordUserIdFrom = null,
        ?string $discordUserIdTo = null,
        ?string $type = null,
        ?string $message = null,
    ) {
        $this->amount = $amount;
        $this->discordUserIdFrom = $discordUserIdFrom;
        $this->discordUserIdTo = $discordUserIdTo;
        $this->type = $type;
        $this->message = $message;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
