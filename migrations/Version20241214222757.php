<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241214222757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Admin table and add roles column to DiscordUser';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discord_user ADD roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\'');
        $this->addSql('ALTER TABLE discord_user RENAME COLUMN name TO username');

        $this->addSql('UPDATE discord_user SET roles = \'["ROLE_ADMIN", "ROLE_USER"]\' WHERE discord_id IN (SELECT discord_id FROM admin)');

        $this->addSql('DROP TABLE admin');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin (discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(discord_id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_880e0d76f85e0677 ON admin (username)');
        $this->addSql('ALTER TABLE discord_user DROP roles');
        $this->addSql('ALTER TABLE discord_user RENAME COLUMN username TO name');
    }
}
