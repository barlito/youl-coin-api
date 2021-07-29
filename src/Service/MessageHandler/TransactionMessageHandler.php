<?php
declare(strict_types=1);

namespace App\Service\MessageHandler;

use App\Message\TransactionMessage;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private WalletRepository $walletRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }
    
    public function __invoke(TransactionMessage $message)
    {
        $wallets = $this->walletRepository->findAll();
        
        $wallets[0]->setAmount(strval(rand(0, 100)));
        $wallets[1]->setAmount(strval(rand(0, 100)));
        
        $this->logger->info('wallet entity', [$wallets[0]->getAmount()]);
        
        $this->entityManager->flush();
    }
}
