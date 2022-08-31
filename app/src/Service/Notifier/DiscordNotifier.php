<?php

declare(strict_types=1);

namespace App\Service\Notifier;

use App\Entity\Transaction;
use App\Money\YoulCoinFormatter;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Bridge\Discord\DiscordOptions;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordAuthorEmbedObject;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordEmbed;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordFieldEmbedObject;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class DiscordNotifier
{
    public function __construct(
        private readonly ChatterInterface $chatter,
        private readonly LoggerInterface $logger,
        private readonly YoulCoinFormatter $youlCoinFormatter,
        private readonly array $discordOptionsParams,
    ) {
    }

    public function notifyNewTransaction(Transaction $transaction): void
    {
        try {
            $chatMessage = new ChatMessage('');
            $discordOptions = (new DiscordOptions())
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    (new DiscordEmbed())
                        ->title($this->discordOptionsParams['transaction']['success_title'])
                        ->author(
                            (new DiscordAuthorEmbedObject())
                                ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                                ->name($this->discordOptionsParams['transaction']['username']),
                        )
                        ->color($this->discordOptionsParams['transaction']['success_color'])
                        ->timestamp(new DateTime())
                        ->addField(
                            (new DiscordFieldEmbedObject())
                                ->name('-' . $this->youlCoinFormatter->format($transaction->getAmount()))
                                ->value("<@{$transaction->getWalletFrom()->getDiscordUser()->getDiscordId()}>")
                                ->inline(true),
                        )
                        ->addField(
                            (new DiscordFieldEmbedObject())
                                ->name('+' . $this->youlCoinFormatter->format($transaction->getAmount()))
                                ->value("<@{$transaction->getWalletTo()->getDiscordUser()->getDiscordId()}>")
                                ->inline(true),
                        ),
                )
            ;

            $chatMessage->options($discordOptions);

            $this->chatter->send($chatMessage);
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), [json_encode($e)]);
        }
    }

    public function notifyErrorOnTransaction(string $errorMessage, string $messageContent): void
    {
        try {
            $chatMessage = new ChatMessage('');
            $discordOptions = (new DiscordOptions())
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    (new DiscordEmbed())
                        ->title($this->discordOptionsParams['transaction']['error_title'])
                        ->author(
                            (new DiscordAuthorEmbedObject())
                                ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                                ->name($this->discordOptionsParams['transaction']['username']),
                        )
                        ->color($this->discordOptionsParams['transaction']['error_color'])
                        ->timestamp(new DateTime())
                        ->addField(
                            (new DiscordFieldEmbedObject())
                                ->name('Error on transaction')
                                ->value($errorMessage),
                        )
                        ->addField(
                            (new DiscordFieldEmbedObject())
                                ->name('Message content')
                                ->value($messageContent),
                        ),
                )
            ;

            $chatMessage->options($discordOptions);

            $this->chatter->send($chatMessage);
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), [json_encode($e)]);
        }
    }
}
