<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929151442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Composite (wallet, created_at, id) indexes for the wallet history query, replacing the plain wallet foreign key indexes they cover';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_723705d19cffc1d');
        $this->addSql('DROP INDEX idx_723705d140322c1f');
        $this->addSql('CREATE INDEX idx_transaction_wallet_from_history ON transaction (wallet_from_id, created_at, id)');
        $this->addSql('CREATE INDEX idx_transaction_wallet_to_history ON transaction (wallet_to_id, created_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_transaction_wallet_from_history');
        $this->addSql('DROP INDEX idx_transaction_wallet_to_history');
        $this->addSql('CREATE INDEX idx_723705d19cffc1d ON transaction (wallet_from_id)');
        $this->addSql('CREATE INDEX idx_723705d140322c1f ON transaction (wallet_to_id)');
    }
}
