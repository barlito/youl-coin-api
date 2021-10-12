<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210909190559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wallet DROP CONSTRAINT fk_7c68921f166d1f9c');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT fk_723705d1166d1f9c');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('DROP TABLE project');
        $this->addSql('ALTER TABLE discord_user ALTER discord_id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE discord_user ALTER discord_id DROP DEFAULT');
        $this->addSql('DROP INDEX idx_723705d1166d1f9c');
        $this->addSql('ALTER TABLE transaction DROP project_id');
        $this->addSql('DROP INDEX uniq_7c68921f166d1f9c');
        $this->addSql('ALTER TABLE wallet DROP project_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE project (id UUID NOT NULL, name VARCHAR(255) NOT NULL, api_key UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_2fb3d0eec912ed9d ON project (api_key)');
        $this->addSql('COMMENT ON COLUMN project.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN project.api_key IS \'(DC2Type:uuid)\'');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE wallet ADD project_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN wallet.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT fk_7c68921f166d1f9c FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_7c68921f166d1f9c ON wallet (project_id)');
        $this->addSql('ALTER TABLE transaction ADD project_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN transaction.project_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT fk_723705d1166d1f9c FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_723705d1166d1f9c ON transaction (project_id)');
        $this->addSql('ALTER TABLE discord_user ALTER discord_id TYPE BIGINT');
        $this->addSql('ALTER TABLE discord_user ALTER discord_id DROP DEFAULT');
    }
}
