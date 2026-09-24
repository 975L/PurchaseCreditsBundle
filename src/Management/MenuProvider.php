<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Management;

use c975L\ConfigBundle\Management\MenuProviderInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditPackCrudController;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditTransactionCrudController;

// Two entries: the packs on sale and the ledger of every account
class MenuProvider implements MenuProviderInterface
{
    public function getMenuSection(): array
    {
        return [
            'label' => 'label.credits',
            'translation_domain' => 'purchasecredits',
            'icon' => 'fas fa-coins',
        ];
    }

    public function getMenus(): array
    {
        return [
            'purchasecredits_pack' => [
                'controller' => CreditPackCrudController::class,
                'label' => 'label.packs',
                'translation_domain' => 'purchasecredits',
                'icon' => 'fas fa-box',
                'description' => 'label.info_packs',
            ],
            'purchasecredits_transaction' => [
                'controller' => CreditTransactionCrudController::class,
                'label' => 'label.transactions',
                'translation_domain' => 'purchasecredits',
                'icon' => 'fas fa-list',
                'description' => 'label.info_transactions',
            ],
        ];
    }

    public function getLinks(): array
    {
        return [];
    }
}
