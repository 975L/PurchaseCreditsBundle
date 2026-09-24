<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Translation;

use c975L\UiBundle\Testing\CatalogueCompletenessCase;

// Every file of the domain says the very same things (see CatalogueCompletenessCase)
class CatalogueCompletenessTest extends CatalogueCompletenessCase
{
    protected static function translationsDirectory(): string
    {
        return \dirname(__DIR__, 2) . '/translations';
    }
}
