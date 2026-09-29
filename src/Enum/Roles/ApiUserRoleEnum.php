<?php

declare(strict_types=1);

namespace App\Enum\Roles;

enum ApiUserRoleEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    // Credit a player from the bank (rewards, airdrops)
    case ROLE_TRANSACTION_BANK_TO_USER = 'ROLE_TRANSACTION_BANK_TO_USER';
    // Debit a player into the bank (purchases)
    case ROLE_TRANSACTION_USER_TO_BANK = 'ROLE_TRANSACTION_USER_TO_BANK';
    case ROLE_TRANSACTION_USER_TO_USER = 'ROLE_TRANSACTION_USER_TO_USER';
    // Read back the transactions this API client created
    case ROLE_TRANSACTION_READ = 'ROLE_TRANSACTION_READ';
    case ROLE_WALLET_READ = 'ROLE_WALLET_READ';
    // Read a player's wallet history, only when it is that player's own X-Player-Token
    case ROLE_WALLET_HISTORY_READ = 'ROLE_WALLET_HISTORY_READ';
    // Trusted client: read any player's wallet history without a player token
    case ROLE_WALLET_HISTORY_READ_ANY = 'ROLE_WALLET_HISTORY_READ_ANY';
}
