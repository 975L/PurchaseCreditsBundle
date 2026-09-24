<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Management;

use c975L\PurchaseCreditsBundle\Controller\Management\CreditPackCrudController;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditTransactionCrudController;
use c975L\PurchaseCreditsBundle\Management\MenuProvider;
use PHPUnit\Framework\TestCase;

class MenuProviderTest extends TestCase
{
    // Its own section, as Shop, Payment and Crowdfunding each keep theirs: credits are a surface of their own
    public function testGetMenuSectionNamesTheBundleSection(): void
    {
        $this->assertSame(['label' => 'label.credits', 'translation_domain' => 'purchasecredits', 'icon' => 'fas fa-coins'], new MenuProvider()->getMenuSection());
    }

    // Two entries, the packs on sale and the ledger, each on its own CRUD
    public function testGetMenusReturnsThePacksAndTheLedger(): void
    {
        $menus = new MenuProvider()->getMenus();

        $this->assertSame(['purchasecredits_pack', 'purchasecredits_transaction'], array_keys($menus));
        $this->assertSame(CreditPackCrudController::class, $menus['purchasecredits_pack']['controller']);
        $this->assertSame(CreditTransactionCrudController::class, $menus['purchasecredits_transaction']['controller']);
    }

    // The onboarding tour builds a step per menu entry, and one without a description shows its label alone (see MenuProviderInterface)
    public function testEveryEntryCarriesWhatTheOnboardingTourReads(): void
    {
        foreach (new MenuProvider()->getMenus() as $slug => $entry) {
            $this->assertSame('purchasecredits', $entry['translation_domain'], $slug);
            $this->assertStringStartsWith('label.info_', $entry['description'], $slug);
            // No role named: the entry's default, site-role-admin, is the very role both CRUDs sit behind
            $this->assertArrayNotHasKey('role', $entry, $slug);
        }
    }

    // No public page of its own: the packs are a block placed wherever the site wants them
    public function testGetLinksIsEmpty(): void
    {
        $this->assertSame([], new MenuProvider()->getLinks());
    }
}
