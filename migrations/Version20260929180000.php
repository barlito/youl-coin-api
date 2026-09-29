<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant ROLE_YTCG_ADMIN to every current ROLE_ADMIN player so youl-tcg admins keep their access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE discord_user
            SET roles = (roles::jsonb || '["ROLE_YTCG_ADMIN"]'::jsonb)::json
            WHERE jsonb_exists(roles::jsonb, 'ROLE_ADMIN') AND NOT jsonb_exists(roles::jsonb, 'ROLE_YTCG_ADMIN')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE discord_user
            SET roles = (roles::jsonb - 'ROLE_YTCG_ADMIN')::json
            WHERE jsonb_exists(roles::jsonb, 'ROLE_YTCG_ADMIN')
            SQL);
    }
}
