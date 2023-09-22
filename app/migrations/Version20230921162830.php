<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230921162830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename notes to name in discordUser table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discord_user ADD name VARCHAR(255)');
        $this->addSql('UPDATE discord_user SET name = notes');
        $this->addSql('ALTER TABLE discord_user ALTER COLUMN name SET NOT NULL');
        $this->addSql('ALTER TABLE discord_user DROP notes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discord_user ADD notes VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE discord_user DROP name');
    }
}
