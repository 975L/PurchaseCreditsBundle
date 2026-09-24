<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Entity;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use PHPUnit\Framework\TestCase;

class CreditTransactionTest extends TestCase
{
    // A line is dated when written, which is what orders the ledger
    public function testANewLineIsDatedNow(): void
    {
        $before = new \DateTimeImmutable();
        $transaction = new CreditTransaction();

        $this->assertGreaterThanOrEqual($before, $transaction->getCreatedAt());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $transaction->getCreatedAt());
        $this->assertSame(0, $transaction->getAmount());
        $this->assertSame('', $transaction->getDescription());
        $this->assertNull($transaction->getReference());
    }

    // The import of a former ledger keeps each line's own date
    public function testAnImportedLineKeepsItsDate(): void
    {
        $date = new \DateTimeImmutable('2021-10-11 10:00:00');

        $this->assertSame($date, new CreditTransaction()->setCreatedAt($date)->getCreatedAt());
    }

    public function testTheSettersAreFluent(): void
    {
        $user = $this->createStub(UserInterface::class);
        $transaction = new CreditTransaction()->setUser($user)->setAmount(-3)->setDescription('Short link')->setReference('42');

        $this->assertSame($user, $transaction->getUser());
        $this->assertSame(-3, $transaction->getAmount());
        $this->assertSame('Short link', $transaction->getDescription());
        $this->assertSame('42', $transaction->getReference());
    }
}
