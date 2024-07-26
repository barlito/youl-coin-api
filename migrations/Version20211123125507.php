<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211123125507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discord_user (discord_id VARCHAR(255) NOT NULL, PRIMARY KEY(discord_id))');
        $this->addSql('CREATE TABLE transaction (id UUID NOT NULL, wallet_from_id UUID DEFAULT NULL, wallet_to_id UUID DEFAULT NULL, amount VARCHAR(255) NOT NULL, message TEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D19CFFC1D ON transaction (wallet_from_id)');
        $this->addSql('CREATE INDEX IDX_723705D140322C1F ON transaction (wallet_to_id)');
        $this->addSql('COMMENT ON COLUMN transaction.wallet_from_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN transaction.wallet_to_id IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE wallet (id UUID NOT NULL, discord_user_id VARCHAR(255) DEFAULT NULL, amount VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C68921FE3F3F7CE ON wallet (discord_user_id)');
        $this->addSql('COMMENT ON COLUMN wallet.id IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
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
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19CFFC1D FOREIGN KEY (wallet_from_id) REFERENCES wallet (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D140322C1F FOREIGN KEY (wallet_to_id) REFERENCES wallet (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921FE3F3F7CE FOREIGN KEY (discord_user_id) REFERENCES discord_user (discord_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE wallet DROP CONSTRAINT FK_7C68921FE3F3F7CE');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D19CFFC1D');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D140322C1F');
        $this->addSql('DROP TABLE discord_user');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE wallet');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
