<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929151442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index transaction.created_at for the wallet history query (ORDER BY created_at DESC)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_transaction_created_at ON transaction (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_transaction_created_at');
    }
}
