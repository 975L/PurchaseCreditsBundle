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
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

// The row actions of each index are icon-only, EasyAdmin's label moving to their hover title
class ActionsTest extends TestCase
{
    // Each CRUD controller with the row actions its index shows
    public static function controllers(): iterable
    {
        yield 'packs' => [CreditPackCrudController::class, ['edit', 'delete']];
        yield 'transactions' => [CreditTransactionCrudController::class, ['detail']];
    }

    /** @param list<string> $rowActions */
    #[DataProvider('controllers')]
    public function testRowActionsAreIconOnly(string $controllerClass, array $rowActions): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $controller = new $controllerClass($this->createStub(ConfigServiceInterface::class), $translator);

        // Starts from EasyAdmin's default actions, as the dashboard hands them to the CRUD controller
        $dashboard = new class extends AbstractDashboardController {
        };
        $actions = $controller->configureActions($dashboard->configureActions())->getAsDto(Crud::PAGE_INDEX);

        foreach ($rowActions as $name) {
            $action = $actions->getAction(Crud::PAGE_INDEX, $name);
            $this->assertNotNull($action, $name);
            $this->assertFalse($action->getLabel(), $name);
            $this->assertSame('action.' . $name, $action->getHtmlAttributes()['title'] ?? null, $name);
        }
    }
}
