<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Service;

use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsDemoFixtureProvider;
use PHPUnit\Framework\TestCase;

// The packs a demo site sells
class PurchaseCreditsDemoFixtureProviderTest extends TestCase
{
    // Three packs on sale, in the order a visitor reads them, and nothing naming an account
    public function testItSellsThreePublishedPacksInOrder(): void
    {
        $packs = iterator_to_array(new PurchaseCreditsDemoFixtureProvider()->getDemoFixtures(), false);

        $this->assertCount(3, $packs);
        $this->assertContainsOnlyInstancesOf(CreditPack::class, $packs);
        $this->assertSame([10, 50, 120], array_map(static fn (CreditPack $pack): ?int => $pack->getCredits(), $packs));
        $this->assertSame([0, 1, 2], array_map(static fn (CreditPack $pack): int => $pack->getPosition(), $packs));
        $this->assertTrue($packs[0]->isPublished());
    }
}
