<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230918190554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index for bank wallet type and create admin table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin (discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(discord_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_880E0D76F85E0677 ON admin (username)');
        $this->addSql('CREATE UNIQUE INDEX wallet_unique_bank_type ON wallet (type) WHERE type = \'bank\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin');
        $this->addSql('DROP INDEX wallet_unique_bank_type');
    }
}
