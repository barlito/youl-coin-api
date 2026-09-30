<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the season_reward transaction type: refuse to deploy while such rows still exist';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM transaction WHERE type = 'season_reward'") > 0,
            'season_reward transactions still exist: the TransactionTypeEnum case is gone, Doctrine could no longer hydrate them.',
        );
    }

    public function down(Schema $schema): void
    {
    }
}
