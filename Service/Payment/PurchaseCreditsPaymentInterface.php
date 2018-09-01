<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service\Payment;

use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;

/**
 * Interface to be called for DI for PurchaseCredits Payment related services
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
interface PurchaseCreditsPaymentInterface
{
    /**
     * Defines payment for Credits purchased
     */
    public function payment(PurchaseCredits $purchaseCredits, $userId);
}
