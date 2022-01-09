<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220107123805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notes fields on Wallet and DiscordUSer entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discord_user ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE wallet ADD notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet DROP notes');
        $this->addSql('ALTER TABLE discord_user DROP notes');
    }
}
