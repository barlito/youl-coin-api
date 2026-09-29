<?php

declare(strict_types=1);

namespace App\Controller;

use App\ApiResource\WalletTransactionView;
use App\Entity\DiscordUser;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Repository\DiscordUserRepository;
use App\Repository\TransactionRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HomepageController extends AbstractController
{
    private const int PAGE_SIZE = 20;
    private const int FLOW_DAYS = 30;

    /**
     * @param list<array{name: string, tagline: string, url: string, logo: string, cta: string}> $hubApps
     */
    #[Route('/', name: 'homepage')]
    public function index(
        #[CurrentUser] DiscordUser $user,
        Request $request,
        TransactionRepository $transactionRepository,
        DiscordUserRepository $discordUserRepository,
        ClockInterface $clock,
        #[Autowire(param: 'app.hub_apps')] array $hubApps,
    ): Response {
        $wallet = $user->getWallet();

        if (!$wallet instanceof Wallet) {
            return $this->render('player/index.html.twig', ['user' => $user, 'wallet' => null, 'hubApps' => $hubApps]);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $history = $transactionRepository->findWalletHistory($wallet, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE);
        $pageCount = max(1, (int) ceil($history->totalItems / self::PAGE_SIZE));

        if ($page > $pageCount) {
            throw new NotFoundHttpException('This history page does not exist.');
        }

        $entries = array_map(
            static fn (Transaction $transaction): WalletTransactionView => WalletTransactionView::fromTransaction($transaction, $wallet),
            $history->transactions,
        );
        $counterpartIds = array_values(array_unique(array_filter(array_column($entries, 'counterpartDiscordId'))));

        return $this->render('player/index.html.twig', [
            'user' => $user,
            'hubApps' => $hubApps,
            'wallet' => $wallet,
            'flow' => $transactionRepository->sumFlowSince(
                $wallet,
                \DateTimeImmutable::createFromInterface($clock->now())->modify(\sprintf('-%d days', self::FLOW_DAYS)),
            ),
            'flowDays' => self::FLOW_DAYS,
            'entries' => $entries,
            'usernames' => $discordUserRepository->findUsernamesByDiscordIds($counterpartIds),
            'page' => $page,
            'pageCount' => $pageCount,
        ]);
    }
}
