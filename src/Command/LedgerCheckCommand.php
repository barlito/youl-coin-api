<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Ledger\LedgerChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ledger:check', description: 'Checks that wallet.amount sums to sum(Mint) - sum(Burn)')]
class LedgerCheckCommand extends Command
{
    public function __construct(private readonly LedgerChecker $ledgerChecker)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->ledgerChecker->check();

        $io->table(['Wallets total', 'Mint total', 'Burn total', 'Mint - Burn'], [[
            $result->walletTotal,
            $result->mintTotal,
            $result->burnTotal,
            $result->getExpectedWalletTotal(),
        ]]);

        if (!$result->isBalanced()) {
            $io->error(\sprintf(
                'Ledger mismatch: wallets total is %s but Mint - Burn is %s.',
                $result->walletTotal,
                $result->getExpectedWalletTotal(),
            ));

            return Command::FAILURE;
        }

        $io->success('Ledger balanced.');

        return Command::SUCCESS;
    }
}
