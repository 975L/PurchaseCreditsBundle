<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\ConfigBundle\Test\ManagementTargetsTestCase;
use c975L\PurchaseCreditsBundle\Management\MenuProvider;
use c975L\PurchaseCreditsBundle\Management\PurchaseCreditsGuidedProjectProvider;
use c975L\PurchaseCreditsBundle\Management\PurchaseCreditsShortcutProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

// Every CRUD controller the menu names and every route the shortcuts name, checked against what the controllers declare (see ManagementTargetsTestCase)
class ManagementTargetsTest extends ManagementTargetsTestCase
{
    protected function managementProviders(): iterable
    {
        return [
            new MenuProvider(),
            // The guided projects generate their urls, so they take the recorder this test case reads them back from
            new PurchaseCreditsGuidedProjectProvider($this->adminUrlGenerator(), $this->createStub(ConfigServiceInterface::class)),
            new PurchaseCreditsShortcutProvider($this->createStub(TranslatorInterface::class), $this->createStub(ConfigServiceInterface::class)),
        ];
    }

    #[\Override]
    protected function controllerDirectories(): array
    {
        return [...parent::controllerDirectories(), __DIR__ . '/../../src/Controller/Management'];
    }
}
