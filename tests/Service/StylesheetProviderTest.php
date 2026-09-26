<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Service;

use c975L\PurchaseCreditsBundle\Service\StylesheetProvider;
use PHPUnit\Framework\TestCase;

// The stylesheet handed to UiBundle is the minified one the bundle actually ships under public/
class StylesheetProviderTest extends TestCase
{
    public function testEveryStylesheetIsShippedByTheBundle(): void
    {
        $stylesheets = new StylesheetProvider()->getStylesheets();

        $this->assertSame(['bundles/c975lpurchasecredits/css/styles.min.css'], $stylesheets);
        foreach ($stylesheets as $stylesheet) {
            $this->assertFileExists(\dirname(__DIR__, 2) . '/public/' . substr($stylesheet, \strlen('bundles/c975lpurchasecredits/')));
        }
    }
}
