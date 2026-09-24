<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Entity;

use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use PHPUnit\Framework\TestCase;

class CreditPackTest extends TestCase
{
    // A pack created in the back office is on sale at the usual VAT until told otherwise
    public function testANewPackIsPublishedFirstAtTheStandardVat(): void
    {
        $pack = new CreditPack();

        $this->assertTrue($pack->isPublished());
        $this->assertSame(20.0, $pack->getVat());
        $this->assertSame(0, $pack->getPosition());
        $this->assertNull($pack->getCredits());
        $this->assertNull($pack->getPrice());
    }

    // Named by its number of credits, which is what the ledger's association field and the back office show
    public function testAPackReadsAsItsNumberOfCredits(): void
    {
        $this->assertSame('25', (string) new CreditPack()->setCredits(25));
        $this->assertSame('', (string) new CreditPack());
    }

    public function testTheSettersAreFluent(): void
    {
        $pack = new CreditPack()->setCredits(10)->setPrice(500)->setVat(5.5)->setPosition(2)->setPublished(false);

        $this->assertSame(10, $pack->getCredits());
        $this->assertSame(500, $pack->getPrice());
        $this->assertSame(5.5, $pack->getVat());
        $this->assertSame(2, $pack->getPosition());
        $this->assertFalse($pack->isPublished());
    }
}
