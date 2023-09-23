<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230923144536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Transaction message field to external_identifier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction RENAME COLUMN message TO external_identifier');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction RENAME COLUMN external_identifier TO message');
    }
}
