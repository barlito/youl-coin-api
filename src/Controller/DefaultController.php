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
            'amount' => "10000",
            'walletIdFrom' => "1ebf09fc-b4ef-6856-88de-e3506d344f3f",
            'walletIdTo' => "1ebf09fc-b4ef-6ef0-8289-e3506d344f3f",
        ];

        $this->dispatchMessage(new TransactionMessage($serializer->serialize($messageContent, 'json')));

        die('ok');
        // ...
    }
}
