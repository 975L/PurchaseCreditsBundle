<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Mcp;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Mcp\PurchaseCreditsMcpProvider;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use Mcp\Server\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class PurchaseCreditsMcpProviderTest extends TestCase
{
    // The tools named under the "credits" key, none of them writing
    public function testTools(): void
    {
        $tools = iterator_to_array($this->provider()->getMcpTools(), false);

        $this->assertSame('credits', $this->provider()->getMcpKey());
        $this->assertSame(['credits_packs', 'credits_transactions', 'credits_totals'], array_map(static fn ($tool): string => $tool->name, $tools));
        $this->assertSame([false, false, false], array_map(static fn ($tool): bool => $tool->writes || $tool->destroys, $tools));
    }

    // A pack read with its credits, price in cents, the currency charged and whether it is on sale
    public function testPacks(): void
    {
        $packs = $this->createStub(CreditPackRepository::class);
        $packs->method('findBy')->willReturn([new CreditPack()->setCredits(10)->setPrice(500)->setPublished(false)]);

        $this->assertSame([['id' => null, 'credits' => 10, 'price' => 500, 'currency' => 'CHF', 'vat' => 20.0, 'published' => false]], $this->provider($packs)->packs());
    }

    // A ledger line read without its account, the limit kept within bounds
    public function testTransactions(): void
    {
        $line = new CreditTransaction()->setAmount(-3)->setDescription('item-1')->setReference('catalog');
        $limits = [];
        $transactions = $this->createStub(CreditTransactionRepository::class);
        $transactions->method('findLatest')->willReturnCallback(static function (int $limit) use (&$limits, $line): array {
            $limits[] = $limit;

            return [$line];
        });

        $read = $this->provider(transactions: $transactions)->transactions(500);
        $this->provider(transactions: $transactions)->transactions(0);
        $this->assertSame(['id', 'date', 'amount', 'kind', 'description', 'reference', 'testMode'], array_keys($read[0]));
        $this->assertSame([-3, 'debit', 'item-1', 'catalog'], [$read[0]['amount'], $read[0]['kind'], $read[0]['description'], $read[0]['reference']]);
        $this->assertSame([50, 1], $limits);
    }

    // The ledger totals passed through as the repository sums them
    public function testTotals(): void
    {
        $transactions = $this->createStub(CreditTransactionRepository::class);
        $transactions->method('totals')->willReturn(['granted' => 10, 'spent' => 3, 'balance' => 7, 'lines' => 2]);

        $this->assertSame(['granted' => 10, 'spent' => 3, 'balance' => 7, 'lines' => 2], $this->provider(transactions: $transactions)->totals());
    }

    // An account that does not manage the credits is refused every tool
    #[DataProvider('tools')]
    public function testRefusedWithoutTheRole(string $tool): void
    {
        $this->expectException(ToolCallException::class);
        $this->provider(granted: false)->{$tool}();
    }

    // Every tool of the provider
    public static function tools(): iterable
    {
        yield ['packs'];
        yield ['transactions'];
        yield ['totals'];
    }

    // The provider for an account managing the credits or not
    private function provider(?CreditPackRepository $packs = null, ?CreditTransactionRepository $transactions = null, bool $granted = true): PurchaseCreditsMcpProvider
    {
        $config = $this->createStub(ConfigServiceInterface::class);
        $config->method('get')->willReturnMap([['site-role-admin', 'ROLE_ADMIN'], ['shop-currency', 'CHF']]);
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn($granted);

        return new PurchaseCreditsMcpProvider($packs ?? $this->createStub(CreditPackRepository::class), $transactions ?? $this->createStub(CreditTransactionRepository::class), $config, $security);
    }
}
