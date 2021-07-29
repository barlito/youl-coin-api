<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210729192016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ALTER api_key TYPE UUID');
        $this->addSql('ALTER TABLE project ALTER api_key DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN project.api_key IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE project ALTER api_key TYPE UUID');
        $this->addSql('ALTER TABLE project ALTER api_key DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN project.api_key IS \'(DC2Type:ulid)\'');
    }
}
