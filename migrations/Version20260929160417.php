<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929160417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store API keys as SHA-256 hash + 6-character prefix instead of plain text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_user ADD api_key_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE api_user ADD api_key_prefix VARCHAR(6) DEFAULT NULL');
        $this->addSql("UPDATE api_user SET api_key_hash = encode(sha256(convert_to(api_key, 'UTF8')), 'hex'), api_key_prefix = left(api_key, 6)");
        $this->addSql('ALTER TABLE api_user ALTER api_key_hash SET NOT NULL');
        $this->addSql('ALTER TABLE api_user ALTER api_key_prefix SET NOT NULL');
        $this->addSql('ALTER TABLE api_user DROP api_key');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC64A0BA848473FE ON api_user (api_key_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Plain API keys cannot be recovered from their hash: regenerate them from the admin.');
    }
}
