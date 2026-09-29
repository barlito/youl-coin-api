<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929103946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transaction.issuer (API client) and make externalIdentifier unique per issuer (idempotency key)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ADD issuer_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1BB9D6FEE FOREIGN KEY (issuer_id) REFERENCES api_user (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_723705D1BB9D6FEE ON transaction (issuer_id)');
        // Existing rows have no issuer: NULLs are distinct, so legacy duplicates cannot break this index
        $this->addSql('CREATE UNIQUE INDEX transaction_issuer_external_identifier_unique ON transaction (issuer_id, external_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1BB9D6FEE');
        $this->addSql('DROP INDEX IDX_723705D1BB9D6FEE');
        $this->addSql('DROP INDEX transaction_issuer_external_identifier_unique');
        $this->addSql('ALTER TABLE transaction DROP issuer_id');
    }
}
