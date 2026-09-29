<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929104254 extends AbstractMigration
{
    private const string SCOPED_ROLES = '["ROLE_TRANSACTION_BANK_TO_USER", "ROLE_TRANSACTION_USER_TO_BANK", "ROLE_TRANSACTION_USER_TO_USER", "ROLE_WALLET_READ"]';

    public function getDescription(): string
    {
        return 'Split ROLE_TRANSACTION_CREATE into scoped transaction roles and ROLE_WALLET_READ (existing API users keep every right)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(\sprintf(
            "UPDATE api_user SET roles = ((roles::jsonb - 'ROLE_TRANSACTION_CREATE') || '%s'::jsonb)::json WHERE jsonb_exists(roles::jsonb, 'ROLE_TRANSACTION_CREATE')",
            self::SCOPED_ROLES,
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(\sprintf(
            "UPDATE api_user SET roles = ((roles::jsonb - ARRAY(SELECT jsonb_array_elements_text('%s'::jsonb))) || '[\"ROLE_TRANSACTION_CREATE\"]'::jsonb)::json WHERE jsonb_exists_any(roles::jsonb, ARRAY['ROLE_TRANSACTION_BANK_TO_USER', 'ROLE_TRANSACTION_USER_TO_BANK', 'ROLE_TRANSACTION_USER_TO_USER'])",
            self::SCOPED_ROLES,
        ));
    }
}
