<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929125638 extends AbstractMigration
{
    // Fixed id so down() can find and remove exactly this row, whether or not it was actually inserted
    private const string GENESIS_TRANSACTION_ID = '00000000-0000-4000-8000-000000000001';

    private const string GENESIS_REASON = 'Genesis: supply existing before the MINT/BURN ledger';

    public function getDescription(): string
    {
        return 'Mint/Burn: nullable transaction wallets, reason and initiatedBy, genesis Mint of the existing supply to the bank (the invariant is global: no per-wallet reconciliation of balances predating the ledger)';
    }

    public function up(Schema $schema): void
    {
        $supply = (string) $this->connection->fetchOne('SELECT COALESCE(SUM(amount::numeric), 0) FROM wallet');
        $this->abortIf(bccomp($supply, '0') < 0, 'The wallets sum to a negative supply: fix the balances before introducing the ledger.');
        $this->abortIf(
            bccomp($supply, '0') > 0 && 0 === (int) $this->connection->fetchOne("SELECT COUNT(*) FROM wallet WHERE type = 'bank'"),
            'Coins exist but there is no bank wallet to receive the genesis Mint: create the bank wallet first.',
        );

        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD initiated_by_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1C4EF1FC7 FOREIGN KEY (initiated_by_id) REFERENCES discord_user (discord_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_723705D1C4EF1FC7 ON transaction (initiated_by_id)');

        // Existing balances predate the ledger: mint them to the bank so sum(wallet.amount) = sum(Mint) - sum(Burn) holds from here on
        $this->addSql(\sprintf(
            <<<'SQL'
                INSERT INTO transaction (id, wallet_from_id, wallet_to_id, amount, external_identifier, type, reason, initiated_by_id, issuer_id, created_at, updated_at)
                SELECT '%s', NULL, bank.id, total.sum_amount::varchar, NULL, 'mint', '%s', NULL, NULL, now(), now()
                FROM wallet bank
                CROSS JOIN (SELECT SUM(amount::numeric) AS sum_amount FROM wallet) total
                WHERE bank.type = 'bank' AND total.sum_amount > 0
                SQL,
            self::GENESIS_TRANSACTION_ID,
            self::GENESIS_REASON,
        ));
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM transaction WHERE type IN ('mint', 'burn') AND id <> :id", ['id' => self::GENESIS_TRANSACTION_ID]) > 0,
            'Mint/Burn transactions other than the genesis exist: they have no wallet and would break the NOT NULL restoration.',
        );

        $this->addSql(\sprintf("DELETE FROM transaction WHERE id = '%s'", self::GENESIS_TRANSACTION_ID));

        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1C4EF1FC7');
        $this->addSql('DROP INDEX IDX_723705D1C4EF1FC7');
        $this->addSql('ALTER TABLE transaction DROP reason');
        $this->addSql('ALTER TABLE transaction DROP initiated_by_id');
        $this->addSql('ALTER TABLE transaction ALTER wallet_from_id SET NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER wallet_to_id SET NOT NULL');
    }
}
