<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Management;

use c975L\ConfigBundle\Management\ShortcutProviderInterface;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\PurchaseCreditsShortcutController;
use c975L\PurchaseCreditsBundle\Management\PurchaseCreditsShortcutProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

// The dashboard tile says what pressing it does, not what the credits currently are - a tile reading "enable" on credits already in test mode would take them out of it
class PurchaseCreditsShortcutProviderTest extends TestCase
{
    public function testTheTileOffersToEnableWhenCreditsAreSoldForReal(): void
    {
        $shortcut = $this->shortcut(false);

        $this->assertSame('label.purchasecredits_test_mode_enable', $shortcut['label']);
        $this->assertFalse($shortcut['active']);
    }

    public function testTheTileOffersToDisableWhenCreditsAreInTestMode(): void
    {
        $shortcut = $this->shortcut(true);

        $this->assertSame('label.purchasecredits_test_mode_disable', $shortcut['label']);
        $this->assertTrue($shortcut['active']);
    }

    // A switch among the toggles, pointing at the route that flips it, for admins only
    public function testTheTileIsAnAdminToggle(): void
    {
        $shortcut = $this->shortcut(false);

        $this->assertSame(PurchaseCreditsShortcutController::TOGGLE_ROUTE_TEST_MODE, $shortcut['route']);
        $this->assertSame(ShortcutProviderInterface::CATEGORY_TOGGLE, $shortcut['category']);
        $this->assertSame('ROLE_ADMIN', $shortcut['role']);
    }

    /** @return array<string, mixed> */
    private function shortcut(bool $enabled): array
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('get')->willReturnCallback(static fn (string $slug): bool | string => 'purchasecredits-test-mode' === $slug ? $enabled : 'ROLE_ADMIN');

        // The stub hands the key back untranslated, which is what the assertions read
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new PurchaseCreditsShortcutProvider($translator, $configService)->getShortcuts()[0];
    }
}
