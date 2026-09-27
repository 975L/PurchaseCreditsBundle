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
use c975L\PurchaseCreditsBundle\Management\AccountSectionProvider;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use PHPUnit\Framework\TestCase;

class AccountSectionProviderTest extends TestCase
{
    // One section carrying the member's own balance and ledger, read in this bundle's domain
    public function testCarriesTheBalanceOfTheMember(): void
    {
        $user = $this->createStub(UserInterface::class);

        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->once())->method('getBalance')->with($user)->willReturn(12);

        $transaction = $this->createStub(CreditTransaction::class);
        $repository = $this->createStub(CreditTransactionRepository::class);
        $repository->method('findForUser')->willReturn([$transaction]);

        $sections = new AccountSectionProvider($creditService, $repository)->getAccountSections($user);

        $this->assertCount(1, $sections);
        $this->assertSame('label.my_credits', $sections[0]['title']);
        $this->assertSame('purchasecredits', $sections[0]['translation_domain']);
        $this->assertSame('@c975LPurchaseCredits/account/_section.html.twig', $sections[0]['template']);
        $this->assertSame(['balance' => 12, 'transactions' => [$transaction], 'latest' => AccountSectionProvider::LATEST], $sections[0]['context']);
    }
}
