<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Exception;

// Thrown by CreditService::spend() when the balance does not cover the amount, nothing having been written
class InsufficientCreditsException extends \RuntimeException
{
    public function __construct(
        public readonly int $balance,
        public readonly int $amount,
    ) {
        parent::__construct(sprintf('Insufficient credits: %d asked, %d available.', $amount, $balance));
    }
}
