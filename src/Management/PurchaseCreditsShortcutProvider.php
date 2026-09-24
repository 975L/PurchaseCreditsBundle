<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Management;

use c975L\ConfigBundle\Management\ShortcutProviderInterface;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\PurchaseCreditsShortcutController;
use Symfony\Contracts\Translation\TranslatorInterface;

// Grouped with the shop's and PaymentBundle's own test switches: the three put a part of the site into a state it is not meant to serve customers in, and an admin looks for them in the same place
class PurchaseCreditsShortcutProvider implements ShortcutProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ConfigServiceInterface $configService,
    ) {
    }

    public function getShortcuts(): array
    {
        $enabled = (bool) $this->configService->get('purchasecredits-test-mode');

        return [
            [
                'label' => $enabled
                    ? $this->translator->trans('label.purchasecredits_test_mode_disable', [], 'purchasecredits')
                    : $this->translator->trans('label.purchasecredits_test_mode_enable', [], 'purchasecredits'),
                'icon' => 'fas fa-vial',
                'route' => PurchaseCreditsShortcutController::TOGGLE_ROUTE_TEST_MODE,
                'active' => $enabled,
                'role' => $this->configService->get('site-role-admin'),
                'category' => ShortcutProviderInterface::CATEGORY_TOGGLE,
            ],
        ];
    }
}
