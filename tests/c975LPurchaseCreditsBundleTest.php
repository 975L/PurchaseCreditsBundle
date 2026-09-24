<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests;

use c975L\PurchaseCreditsBundle\c975LPurchaseCreditsBundle;
use c975L\PurchaseCreditsBundle\Management\MenuProvider;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class c975LPurchaseCreditsBundleTest extends TestCase
{
    // Loaded the way the kernel does, which also checks that config/services.yaml parses and wires
    public function testLoadExtensionImportsServicesYaml(): void
    {
        $container = new ContainerBuilder();

        new c975LPurchaseCreditsBundle()->getContainerExtension()->load([], $container);

        $this->assertTrue($container->hasDefinition(MenuProvider::class));
        $this->assertTrue($container->hasAlias(CreditServiceInterface::class));
        $this->assertTrue($container->hasDefinition('purchasecredits.block.packs'));
    }

    // The "ui.block" tag names its form and its template as strings, which nothing compiles: a renamed file leaves the kind in the picker and breaks the page it is placed on
    public function testThePacksBlockNamesAFormAndATemplateThatExist(): void
    {
        $container = new ContainerBuilder();
        new c975LPurchaseCreditsBundle()->getContainerExtension()->load([], $container);

        $tag = $container->getDefinition('purchasecredits.block.packs')->getTag('ui.block')[0];

        $this->assertSame('purchasecredits_packs', $tag['kind']);
        $this->assertTrue(class_exists($tag['form']), sprintf('%s does not exist', $tag['form']));
        $this->assertFileExists(\dirname(__DIR__) . '/templates/' . substr($tag['template'], \strlen('@c975LPurchaseCredits/')));
        // The balance shown beside the packs belongs to whoever reads the page
        $this->assertFalse($tag['cacheable']);
    }

    public function testGetPathReturnsTheBundleRootDirectory(): void
    {
        $this->assertSame(\dirname(__DIR__), new c975LPurchaseCreditsBundle()->getPath());
    }
}
