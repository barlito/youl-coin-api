<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230917211733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Unique Index Constraint on Wallet Type field only for BANK value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX wallet_unique_bank_type on wallet(type) WHERE type = \'bank\';');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX wallet_unique_bank_type;');
    }
}
