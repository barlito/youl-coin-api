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
            'amount' => rand(-100, 10),
            'walletIdFrom' => "1ec368f7-dcfe-6960-b6c1-3dde5955f27c",
            'walletIdTo' => "1ec368f7-dcff-6c8e-aec4-3dde5955f27c",
            'type' => 'classic',
            'message' => 'test',
        ];

        $this->dispatchMessage(new TransactionMessage($serializer->serialize($messageContent, 'json')));

        die('ok');
        // ...
    }
}
