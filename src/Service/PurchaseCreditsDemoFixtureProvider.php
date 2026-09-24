<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\UiBundle\Contract\DemoFixtureProviderInterface;

// The packs a demo site sells, without any ledger: its lines name accounts, which are the site's own
class PurchaseCreditsDemoFixtureProvider implements DemoFixtureProviderInterface
{
    // Credits, price in cents: small, usual and large
    private const array PACKS = [[10, 500], [50, 2000], [120, 4000]];

    public function getDemoFixtures(): iterable
    {
        foreach (self::PACKS as $position => [$credits, $price]) {
            yield new CreditPack()
                ->setCredits($credits)
                ->setPrice($price)
                ->setPosition($position)
                ->setPublished(true)
            ;
        }
    }
}
