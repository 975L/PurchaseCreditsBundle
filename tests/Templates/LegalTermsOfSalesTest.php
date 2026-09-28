<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Templates;

use PHPUnit\Framework\TestCase;

// UiBundle's "france/terms-of-sales" model includes these by name, "ignore missing" hiding any mistake: each locale must exist, carry the "credits" unit and say what waiving the withdrawal covers
class LegalTermsOfSalesTest extends TestCase
{
    public function testEachLocaleShipsTheCreditsSection(): void
    {
        foreach (['fr', 'en', 'es'] as $locale) {
            $path = \dirname(__DIR__, 2) . '/templates/legal/terms-of-sales.' . $locale . '.html.twig';
            $this->assertFileExists($path);

            $template = (string) file_get_contents($path);
            $this->assertStringContainsString('<section data-legal-id="credits">', $template, $locale);
            $this->assertStringContainsString('L 221-28', $template, $locale);
        }
    }
}
