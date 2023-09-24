<?php

declare(strict_types=1);

namespace App\Enum\Roles;

enum ApiUserRoleEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_TRANSACTION_CREATE = 'ROLE_TRANSACTION_CREATE';
}
