<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Controller;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\ConfigBundle\Service\LocalizedRouteNegotiator;
use c975L\ConfigBundle\Service\SiteLocales;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// The member's whole credit history, a page at a time, which the account page's section leads to past its latest lines (see account/_section.html.twig)
#[IsGranted('ROLE_USER')]
class AccountCreditsController extends AbstractController
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly CreditServiceInterface $creditService,
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly LocalizedRouteNegotiator $negotiator,
        private readonly SiteLocales $siteLocales,
    ) {
    }

    // In every language the site declares, as PaymentBundle's order history. A page past the last one answers the last
    #[Route('/{_locale}/account/credits', name: 'purchasecredits_account_credits_localized', requirements: ['_locale' => '%c975l_config.locales_pattern%'], methods: ['GET'])]
    #[Route('/account/credits', name: 'purchasecredits_account_credits', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $askedLanguage = $this->negotiator->redirectToAskedLanguage($request, $this->siteLocales->all(), 'purchasecredits_account_credits');
        if (null !== $askedLanguage) {
            return $this->negotiator->vary($request, $askedLanguage);
        }

        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            throw $this->createNotFoundException();
        }

        $pageCount = max(1, (int) ceil($this->transactionRepository->countForUser($user) / self::PER_PAGE));
        $currentPage = min($pageCount, max(1, $request->query->getInt('page', 1)));

        return $this->negotiator->vary($request, $this->render('@c975LPurchaseCredits/account/credits.html.twig', [
            'balance' => $this->creditService->getBalance($user),
            'transactions' => $this->transactionRepository->findForUser($user, self::PER_PAGE, ($currentPage - 1) * self::PER_PAGE),
            'currentPage' => $currentPage,
            'pageCount' => $pageCount,
        ]));
    }
}
