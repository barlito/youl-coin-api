<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210729184348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discord_user (id UUID NOT NULL, discord_id BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN discord_user.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE project (id UUID NOT NULL, name VARCHAR(255) NOT NULL, api_key UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EEC912ED9D ON project (api_key)');
        $this->addSql('COMMENT ON COLUMN project.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN project.api_key IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE transaction (id UUID NOT NULL, wallet_from_id UUID DEFAULT NULL, wallet_to_id UUID DEFAULT NULL, project_id UUID DEFAULT NULL, amount VARCHAR(255) NOT NULL, message TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D19CFFC1D ON transaction (wallet_from_id)');
        $this->addSql('CREATE INDEX IDX_723705D140322C1F ON transaction (wallet_to_id)');
        $this->addSql('CREATE INDEX IDX_723705D1166D1F9C ON transaction (project_id)');
        $this->addSql('COMMENT ON COLUMN transaction.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN transaction.wallet_from_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN transaction.wallet_to_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN transaction.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE wallet (id UUID NOT NULL, discord_user_id UUID DEFAULT NULL, project_id UUID DEFAULT NULL, amount VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C68921FE3F3F7CE ON wallet (discord_user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C68921F166D1F9C ON wallet (project_id)');
        $this->addSql('COMMENT ON COLUMN wallet.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN wallet.discord_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN wallet.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19CFFC1D FOREIGN KEY (wallet_from_id) REFERENCES wallet (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D140322C1F FOREIGN KEY (wallet_to_id) REFERENCES wallet (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921FE3F3F7CE FOREIGN KEY (discord_user_id) REFERENCES discord_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE wallet DROP CONSTRAINT FK_7C68921FE3F3F7CE');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1166D1F9C');
        $this->addSql('ALTER TABLE wallet DROP CONSTRAINT FK_7C68921F166D1F9C');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D19CFFC1D');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D140322C1F');
        $this->addSql('DROP TABLE discord_user');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE wallet');
    }
}
