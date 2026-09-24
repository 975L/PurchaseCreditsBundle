<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Controller\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditPackCrudController;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditTransactionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Each index shows the description its menu item carries, so the text is the page's own and not an onboarding-only string
class IndexTemplatesTest extends TestCase
{
    // Each CRUD controller with the description key its menu item declares
    public static function controllers(): iterable
    {
        yield 'packs' => [CreditPackCrudController::class, 'label.info_packs'];
        yield 'transactions' => [CreditTransactionCrudController::class, 'label.info_transactions'];
    }

    #[DataProvider('controllers')]
    public function testIndexTemplateShowsTheMenuDescription(string $controllerClass, string $descriptionKey): void
    {
        $controller = new $controllerClass($this->createStub(ConfigServiceInterface::class));
        $template = $controller->configureCrud(Crud::new())->getAsDto()->getOverriddenTemplates()['crud/index'] ?? null;

        $this->assertIsString($template);
        $path = str_replace('@c975LPurchaseCredits/', \dirname(__DIR__, 3) . '/templates/', $template);
        $this->assertFileExists($path);
        $this->assertStringContainsString("'" . $descriptionKey . "'|trans", (string) file_get_contents($path));
    }
}
