<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\TransactionMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DefaultController extends AbstractController
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    #[Route('/default', name: 'default')]
    public function index(SerializerInterface $serializer)
    {
        $messageContent = [
            'amount' => rand(-10, 10),
            'discordUserIdFrom' => '188967649332428800',
            'discordUserIdTo' => '232457563910832129',
            'type' => 'classic',
            'message' => 'test',
        ];

        $this->messageBus->dispatch(new TransactionMessage($serializer->serialize($messageContent, 'json')));

        dd('ok');
        // ...
    }
}
