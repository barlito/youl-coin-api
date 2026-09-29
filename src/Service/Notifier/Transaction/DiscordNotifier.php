<?php

declare(strict_types=1);

namespace App\Service\Notifier\Transaction;

use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Service\Notifier\Transaction\Abstract\Interface\TransactionNotifierInterface;
use App\Service\Util\MoneyUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Bridge\Discord\DiscordOptions;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordAuthorEmbedObject;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordEmbed;
use Symfony\Component\Notifier\Bridge\Discord\Embeds\DiscordFieldEmbedObject;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class DiscordNotifier implements TransactionNotifierInterface
{
    public function __construct(
        private readonly ChatterInterface $chatter,
        private readonly LoggerInterface $logger,
        private readonly MoneyUtil $moneyUtil,
        private readonly array $discordOptionsParams,
    ) {
    }

    public function notifyNewTransaction(Transaction $transaction): void
    {
        try {
            $chatMessage = new ChatMessage('');
            $fromUser = $this->describeWallet($transaction->getWalletFrom(), 'Mint');
            $toUser = $this->describeWallet($transaction->getWalletTo(), 'Burn');

            $discordOptions = new DiscordOptions()
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    new DiscordEmbed()
                        ->title($this->discordOptionsParams['transaction']['success_title'])
                        ->author(
                            new DiscordAuthorEmbedObject()
                                ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                                ->name($this->discordOptionsParams['transaction']['username']),
                        )
                        ->color($this->discordOptionsParams['transaction']['success_color'])
                        ->timestamp(new \DateTime())
                        ->addField(
                            new DiscordFieldEmbedObject()
                                ->name('-' . $this->moneyUtil->getFormattedMoney($transaction->getAmount()))
                                ->value($fromUser)
                                ->inline(true),
                        )
                        ->addField(
                            new DiscordFieldEmbedObject()
                                ->name('+' . $this->moneyUtil->getFormattedMoney($transaction->getAmount()))
                                ->value($toUser)
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

    // Missing wallet only happens on Mint (no walletFrom) or Burn (no walletTo)
    private function describeWallet(?Wallet $wallet, string $missingWalletLabel): string
    {
        if (!$wallet instanceof Wallet) {
            return $missingWalletLabel;
        }

        return $wallet->getDiscordUser() instanceof DiscordUser ? "<@{$wallet->getDiscordUser()?->getDiscordId()}>" : 'Bank Wallet';
    }

    public function notifyErrorOnTransaction(string $errorMessage, string $messageContent): void
    {
        try {
            $chatMessage = new ChatMessage('');
            $discordOptions = new DiscordOptions()
                ->username($this->discordOptionsParams['transaction']['username'])
                ->avatarUrl($this->discordOptionsParams['transaction']['avatar_url'])
                ->addEmbed(
                    new DiscordEmbed()
                        ->title($this->discordOptionsParams['transaction']['error_title'])
                        ->author(
                            new DiscordAuthorEmbedObject()
                                ->iconUrl($this->discordOptionsParams['transaction']['avatar_url'])
                                ->name($this->discordOptionsParams['transaction']['username']),
                        )
                        ->color($this->discordOptionsParams['transaction']['error_color'])
                        ->timestamp(new \DateTime())
                        ->addField(
                            new DiscordFieldEmbedObject()
                                ->name('Error on transaction')
                                ->value($errorMessage),
                        )
                        ->addField(
                            new DiscordFieldEmbedObject()
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
