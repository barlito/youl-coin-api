<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929125638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mint/Burn: nullable transaction wallets, reason and initiatedBy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD initiated_by_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1C4EF1FC7 FOREIGN KEY (initiated_by_id) REFERENCES discord_user (discord_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_723705D1C4EF1FC7 ON transaction (initiated_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM transaction WHERE type IN ('mint', 'burn')") > 0,
            'Mint/Burn transactions exist: they have no wallet and would break the NOT NULL restoration.',
        );

        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1C4EF1FC7');
        $this->addSql('DROP INDEX IDX_723705D1C4EF1FC7');
        $this->addSql('ALTER TABLE transaction DROP reason');
        $this->addSql('ALTER TABLE transaction DROP initiated_by_id');
        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id SET NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id SET NOT NULL');
    }
}
