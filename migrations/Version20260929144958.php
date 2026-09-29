<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929144958 extends AbstractMigration
{
    // Copied from EconomySettings::DEFAULT_WELCOME_BONUS_AMOUNT on purpose: a migration must not change when the entity does
    private const string DEFAULT_WELCOME_BONUS_AMOUNT = '100000000000';

    public function getDescription(): string
    {
        return 'Welcome bonus: singleton EconomySettings row (amount) and a one-per-wallet unique index on welcome_bonus transactions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE economy_settings (id SMALLINT NOT NULL, welcome_bonus_amount VARCHAR(255) NOT NULL, CONSTRAINT economy_settings_singleton CHECK (id = 1), PRIMARY KEY (id))');
        $this->addSql(\sprintf(
            'INSERT INTO economy_settings (id, welcome_bonus_amount) VALUES (1, \'%s\')',
            self::DEFAULT_WELCOME_BONUS_AMOUNT,
        ));

        // Enforced in the DB, not just in application code: a concurrent double-grant cannot slip through
        $this->addSql("CREATE UNIQUE INDEX transaction_unique_welcome_bonus_per_wallet ON transaction (wallet_to_id) WHERE type = 'welcome_bonus'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX transaction_unique_welcome_bonus_per_wallet');
        $this->addSql('DROP TABLE economy_settings');
    }
}
