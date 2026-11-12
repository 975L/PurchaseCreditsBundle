<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests;

use c975L\ConfigBundle\Test\CodeRulesTestCase;

// The audit's rules replayed on the bundle's own code (see ConfigBundle's CodeRulesTestCase)
class CodeRulesTest extends CodeRulesTestCase
{
    // Where the bundle's code lives
    protected function sourceDirectory(): string
    {
        return __DIR__ . '/../src';
    }

    // The namespace of that code
    protected function sourceNamespace(): string
    {
        return 'c975L\PurchaseCreditsBundle';
    }

    // The legitimate exceptions, each with its reason
    protected function allowed(): array
    {
        return [
            self::BOUNDED_NUMBER => [
                'c975L\PurchaseCreditsBundle\Entity\CreditTransaction::amount' => 'A ledger line is signed: positive when granted, negative when spent or taken back',
            ],
        ];
    }
}
