<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

// The balance sentence covers every balance, a negative one included (a manual ledger line can take a user below zero)
class BalancePluralTest extends TestCase
{
    // Each locale against a negative, a zero, a single and a plural balance
    public static function balances(): iterable
    {
        foreach (['en', 'fr', 'es'] as $locale) {
            foreach ([-3, 0, 1, 5] as $count) {
                yield $locale . ' ' . $count => [$locale, $count];
            }
        }
    }

    #[DataProvider('balances')]
    public function testEveryBalanceHasASentence(string $locale, int $count): void
    {
        $translator = new Translator($locale);
        $translator->addLoader('xliff', new XliffFileLoader());
        $translator->addResource('xliff', \dirname(__DIR__, 2) . '/translations/purchasecredits.' . $locale . '.xlf', $locale, 'purchasecredits');

        $sentence = $translator->trans('label.balance', ['%count%' => $count], 'purchasecredits');

        $this->assertNotSame('label.balance', $sentence);
        $this->assertStringNotContainsString('|', $sentence);
    }
}
