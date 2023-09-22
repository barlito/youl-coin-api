<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230921162156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename notes to name in wallet table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE wallet SET name = notes');
        $this->addSql('ALTER TABLE wallet ALTER COLUMN name SET NOT NULL');
        $this->addSql('ALTER TABLE wallet DROP notes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet ADD notes VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE wallet DROP name');
    }
}
