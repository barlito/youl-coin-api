<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\FailedNotification;
use App\Enum\Roles\RoleEnum;
use App\Message\TransactionCommitted;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(RoleEnum::ROLE_ADMIN->value)]
class FailedNotificationsController extends AbstractController
{
    private const int LIST_LIMIT = 50;

    public function __construct(
        #[Autowire(service: 'messenger.transport.outbox')]
        private readonly TransportInterface $outbox,
        #[Autowire(service: 'messenger.transport.failed')]
        private readonly TransportInterface $failed,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin/failed-notifications', name: 'admin_failed_notifications')]
    public function index(): Response
    {
        $envelopes = $this->failed instanceof ListableReceiverInterface ? $this->failed->all(self::LIST_LIMIT) : [];

        return $this->render('admin/failed-notifications/index.html.twig', [
            'pendingCount' => $this->countOf($this->outbox),
            'failedCount' => $this->countOf($this->failed),
            'limit' => self::LIST_LIMIT,
            'failures' => array_map($this->toFailure(...), [...$envelopes]),
        ]);
    }

    private function countOf(TransportInterface $transport): ?int
    {
        return $transport instanceof MessageCountAwareInterface ? $transport->getMessageCount() : null;
    }

    private function toFailure(Envelope $envelope): FailedNotification
    {
        $message = $envelope->getMessage();
        $transactionId = $message instanceof TransactionCommitted ? $message->transactionId : null;
        $redelivery = $envelope->last(RedeliveryStamp::class);

        return new FailedNotification(
            (string) $envelope->last(TransportMessageIdStamp::class)?->getId(),
            $redelivery?->getRedeliveredAt(),
            new \ReflectionClass($message)->getShortName(),
            null === $transactionId ? null : $this->adminUrlGenerator
                ->setDashboard(DashboardController::class)
                ->setController(TransactionCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($transactionId)
                ->generateUrl(),
            $transactionId,
            $envelope->last(ErrorDetailsStamp::class)?->getExceptionMessage() ?? '',
            $redelivery?->getRetryCount() ?? 0,
        );
    }
}
