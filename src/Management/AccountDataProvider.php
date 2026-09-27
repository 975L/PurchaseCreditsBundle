<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Management;

use c975L\ConfigBundle\Account\AccountDataProviderInterface;
use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;

// The credits part of a member's data export (ConfigBundle's /account/export): the balance and every movement of the ledger
class AccountDataProvider implements AccountDataProviderInterface
{
    public function __construct(
        private readonly CreditServiceInterface $creditService,
        private readonly CreditTransactionRepository $transactionRepository,
    ) {
    }

    // Under "credits", the movements latest first
    public function getAccountData(UserInterface $user): array
    {
        $transactions = $this->transactionRepository->findForUser($user);
        if ([] === $transactions) {
            return [];
        }

        return ['credits' => [
            'balance' => $this->creditService->getBalance($user),
            'transactions' => array_map(static fn (CreditTransaction $transaction): array => [
                'date' => $transaction->getCreatedAt(),
                'amount' => $transaction->getAmount(),
                'description' => $transaction->getDescription(),
                'reference' => $transaction->getReference(),
            ], $transactions),
        ]];
    }
}
