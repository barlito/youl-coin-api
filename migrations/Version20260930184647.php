<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930184647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the optional transaction description (140 chars) and the API client display name shown to players';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_user ADD display_name VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD description VARCHAR(140) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction DROP description');
        $this->addSql('ALTER TABLE api_user DROP display_name');
    }
}
