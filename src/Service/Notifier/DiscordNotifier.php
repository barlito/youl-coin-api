<?php

declare(strict_types=1);

namespace App\Service\Notifier;

use App\Entity\Transaction;
use App\Money\YoulCoinFormatter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Bridge\Discord\DiscordOptions;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordEmbed;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordFieldEmbedObject;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordFooterEmbedObject;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class DiscordNotifier
{
    public function __construct(
        private ChatterInterface $chatter,
        private LoggerInterface  $logger,
        private array $discordOptionsParams,
    ) {
    }

    public function notifyNewTransaction(Transaction $transaction)
    {
        try {
            $chatMessage = new ChatMessage('');
            $discordOptions = (new DiscordOptions())
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    (new DiscordEmbed())
                    ->title($this->discordOptionsParams['transaction']['success_title'])
                    ->color($this->discordOptionsParams['transaction']['success_color'])
                    ->timestamp(new \DateTime())
                    ->addField(
                        (new DiscordFieldEmbedObject())
                        ->name('-' . YoulCoinFormatter::format($transaction->getAmount()))
                        ->value("<@{$transaction->getWalletFrom()->getDiscordUser()->getDiscordId()}>")
                        ->inline(true)
                    )
                    ->addField(
                        (new DiscordFieldEmbedObject())
                        ->name('+' . YoulCoinFormatter::format($transaction->getAmount()))
                        ->value("<@{$transaction->getWalletTo()->getDiscordUser()->getDiscordId()}>")
                        ->inline(true)
                    )
                    ->footer(
                        (new DiscordFooterEmbedObject())
                        ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                    )
                );

            $chatMessage->options($discordOptions);

            $this->chatter->send($chatMessage);
        } catch (TransportExceptionInterface | LogicException $e) {
            $this->logger->critical($e->getMessage(), [json_encode($e)]);
        }
    }

    public function notifyErrorOnTransaction(string $errorMessage, string $messageContent)
    {
        try {
            $chatMessage = new ChatMessage('');
            $discordOptions = (new DiscordOptions())
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    (new DiscordEmbed())
                    ->title($this->discordOptionsParams['transaction']['error_title'])
                    ->color($this->discordOptionsParams['transaction']['error_color'])
                    ->timestamp(new \DateTime())
                    ->addField(
                        (new DiscordFieldEmbedObject())
                        ->name("Error on transaction")
                        ->value($errorMessage)
                    )
                    ->addField(
                        (new DiscordFieldEmbedObject())
                        ->name("Message content")
                        ->value($messageContent)
                    )
                    ->footer(
                        (new DiscordFooterEmbedObject())
                        ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                    )
                );

            $chatMessage->options($discordOptions);

            $this->chatter->send($chatMessage);
        } catch (TransportExceptionInterface | LogicException $e) {
            $this->logger->critical($e->getMessage(), [json_encode($e)]);
        }
    }
}
