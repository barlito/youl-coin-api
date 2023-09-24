<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230924004122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add API User Entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_user (id UUID NOT NULL, name VARCHAR(180) NOT NULL, api_key VARCHAR(180) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC64A0BA5E237E06 ON api_user (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC64A0BAC912ED9D ON api_user (api_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_user');
    }
}
