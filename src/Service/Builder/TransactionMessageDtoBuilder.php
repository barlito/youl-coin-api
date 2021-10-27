<?php

namespace App\Service\Builder;

use App\DTO\TransactionMessageDTO;
use App\Entity\Wallet;
use App\Message\TransactionMessage;
use App\Repository\DiscordUserRepository;
use App\Repository\WalletRepository;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class TransactionMessageDtoBuilder
{
    public function __construct(
        private DiscordUserRepository $discordUserRepository,
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
            $this->getDiscordUserWallet($content['discordUserIdFrom'] ?? ''),
            $this->getDiscordUserWallet($content['discordUserIdTo'] ?? ''),
            $content['type'] ?? null,
            $content['message'] ?? null
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
    
    private function getDiscordUserWallet(mixed $discordUserIdFrom): ?Wallet
    {
        $discordUser = $this->discordUserRepository->find($discordUserIdFrom);
        if(null !== $discordUser){
            return $discordUser->getWallet();
        }
        
        return null;
    }
}
