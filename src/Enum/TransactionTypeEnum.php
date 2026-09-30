<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case CLASSIC = 'classic';
    case AIR_DROP = 'air_drop';
    case REGULATION = 'regulation';
    // Admin-only: creates coins out of nowhere into the bank wallet
    case MINT = 'mint';
    // Admin-only: destroys coins held by the bank wallet
    case BURN = 'burn';
    // System-only: bank to a fresh player wallet, granted at login (DiscordAuthenticator)
    case WELCOME_BONUS = 'welcome_bonus';
    // Apps (youl-tcg): a player pays the bank
    case PURCHASE = 'purchase';
    // Apps: the bank rewards a player
    case REWARD = 'reward';
    // Apps market, escrowed by the bank: the buyer pays the bank, the bank pays the seller or refunds the buyer
    case MARKET_PAYMENT = 'market_payment';
    case MARKET_PAYOUT = 'market_payout';
    case MARKET_REFUND = 'market_refund';

    public function isSupplyChange(): bool
    {
        return \in_array($this, [self::MINT, self::BURN], true);
    }

    public function isSystemOnly(): bool
    {
        return \in_array($this, [self::MINT, self::BURN, self::WELCOME_BONUS], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CLASSIC => 'Transfert classique',
            self::AIR_DROP => 'Air drop',
            self::REGULATION => 'Régulation',
            self::MINT => 'Mint',
            self::BURN => 'Burn',
            self::WELCOME_BONUS => 'Bonus de bienvenue',
            self::PURCHASE => 'Achat',
            self::REWARD => 'Récompense',
            self::MARKET_PAYMENT => 'Marché — paiement',
            self::MARKET_PAYOUT => 'Marché — vente',
            self::MARKET_REFUND => 'Marché — remboursement',
        };
    }
}
