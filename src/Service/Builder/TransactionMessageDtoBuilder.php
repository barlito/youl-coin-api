<?php

namespace App\Service\Builder;

use App\DTO\TransactionMessageDTO;
use App\Message\TransactionMessage;
use App\Repository\WalletRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class TransactionMessageDtoBuilder
{
    public function __construct(
        private WalletRepository $walletRepository,
    ) {
    }

    /**
     * @throws UnexpectedValueException
     */
    public function build(TransactionMessage $transactionMessage): TransactionMessageDTO
    {
        $content = $this->decodeMessageContent($transactionMessage);

        return new TransactionMessageDTO(
            $content['amount'] ?? null,
            $this->walletRepository->find($content['walletIdFrom'] ?? ""),
            $this->walletRepository->find($content['walletIdTo'] ?? ""),
        );
    }

    /**
     * @throws UnexpectedValueException
     */
    private function decodeMessageContent(TransactionMessage $transactionMessage): array
    {
        return $this->getSerializer()->decode($transactionMessage->getContent(), 'json');
    }

    private function getSerializer(): SerializerInterface
    {
        return new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
    }
}
