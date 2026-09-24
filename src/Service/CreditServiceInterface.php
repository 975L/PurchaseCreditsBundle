<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Exception\InsufficientCreditsException;

// What a site's own services call to read, grant and spend credits - the only door to the ledger
interface CreditServiceInterface
{
    // The credits a user has left
    public function getBalance(UserInterface $user): int;

    // Adds credits to a user (a purchase, a gift, a sign-up bonus)
    public function grant(UserInterface $user, int $amount, string $description, ?string $reference = null): CreditTransaction;

    // Takes credits from a user, or writes nothing at all
    /** @throws InsufficientCreditsException when the balance does not cover the amount */
    public function spend(UserInterface $user, int $amount, string $description, ?string $reference = null): CreditTransaction;
}
