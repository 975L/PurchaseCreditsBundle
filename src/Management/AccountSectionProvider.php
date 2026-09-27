<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Management;

use c975L\ConfigBundle\Account\AccountSectionProviderInterface;
use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;

// The member's balance on their own page (see ConfigBundle's AccountController), with the packs on sale to top it up there and then - the bundle has no page of its own to link to, its packs living on whatever page the site placed the block
class AccountSectionProvider implements AccountSectionProviderInterface
{
    // The movements the section shows, one more read to know whether the history page has more
    public const int LATEST = 5;

    public function __construct(
        private readonly CreditServiceInterface $creditService,
        private readonly CreditTransactionRepository $transactionRepository,
    ) {
    }

    // After PaymentBundle's orders, a purchase of credits being one of them
    public function getAccountSections(UserInterface $user): array
    {
        return [[
            'title' => 'label.my_credits',
            'translation_domain' => 'purchasecredits',
            'template' => '@c975LPurchaseCredits/account/_section.html.twig',
            'context' => ['balance' => $this->creditService->getBalance($user), 'transactions' => $this->transactionRepository->findForUser($user, self::LATEST + 1), 'latest' => self::LATEST],
            'position' => 20,
        ]];
    }
}
