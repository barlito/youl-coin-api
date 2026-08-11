<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811152323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allowed_discord_user table holding the dynamic part of the Discord whitelist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE allowed_discord_user (discord_id VARCHAR(255) NOT NULL, label VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(discord_id))');

        $this->addSql("INSERT INTO allowed_discord_user (discord_id, label, created_at, updated_at) VALUES ('297453953120075778', 'Whitelisted on migration', NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE allowed_discord_user');
    }
}
