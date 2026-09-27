<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Management;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Management\AccountDataProvider;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use PHPUnit\Framework\TestCase;

class AccountDataProviderTest extends TestCase
{
    // The balance and every movement of the ledger
    public function testExportsTheBalanceAndTheLedger(): void
    {
        $transaction = $this->createStub(CreditTransaction::class);
        $transaction->method('getAmount')->willReturn(5);
        $transaction->method('getDescription')->willReturn('Sign-up bonus');
        $transaction->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-09-27'));

        $data = $this->provider([$transaction], 5)->getAccountData($this->createStub(UserInterface::class));

        $this->assertSame(5, $data['credits']['balance']);
        $this->assertSame('Sign-up bonus', $data['credits']['transactions'][0]['description']);
    }

    // An account that never had a credit has no "credits" part
    public function testNothingWithoutMovements(): void
    {
        $this->assertSame([], $this->provider([], 0)->getAccountData($this->createStub(UserInterface::class)));
    }

    /** @param list<CreditTransaction> $transactions */
    private function provider(array $transactions, int $balance): AccountDataProvider
    {
        $creditService = $this->createStub(CreditServiceInterface::class);
        $creditService->method('getBalance')->willReturn($balance);
        $repository = $this->createStub(CreditTransactionRepository::class);
        $repository->method('findForUser')->willReturn($transactions);

        return new AccountDataProvider($creditService, $repository);
    }
}
