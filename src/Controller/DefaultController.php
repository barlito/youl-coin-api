<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\TransactionMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DefaultController extends AbstractController
{
    #[Route('/default', name: 'default')]
    public function index(SerializerInterface $serializer)
    {
        $messageContent = [
            'amount' => rand(-10, 10),
            'discordUserIdFrom' => '1ec37378-b221-614e-bbe9-1f315163a541',
            'discordUserIdTo' => '1ec37378-b221-62d4-b21c-1f315163a541',
            'type' => 'classic',
            'message' => 'test',
        ];

        $this->dispatchMessage(new TransactionMessage($serializer->serialize($messageContent, 'json')));

        exit('ok');
        // ...
    }
}
