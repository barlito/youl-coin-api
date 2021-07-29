<?php
declare(strict_types=1);

namespace App\Controller;

use App\Message\TransactionMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    
    #[Route('/default', name: 'default')]
    public function index()
    {
        $this->dispatchMessage(new TransactionMessage('Look! I created a message from this!'));

        die('ok');
// ...
    }
}
