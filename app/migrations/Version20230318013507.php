<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230318013507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id SET NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id DROP NOT NULL');
    }
}
