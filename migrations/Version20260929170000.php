<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset the economy: clear the transaction history and the pending outbox, set every wallet (bank included) to 0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM messenger_messages WHERE queue_name = 'outbox'");
        $this->addSql('DELETE FROM transaction');
        $this->addSql("UPDATE wallet SET amount = '0', updated_at = NOW() AT TIME ZONE 'UTC'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The wiped history and balances cannot be rebuilt: restore the database backup taken before the deployment.');
    }
}
