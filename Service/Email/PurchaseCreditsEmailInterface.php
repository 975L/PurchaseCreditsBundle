<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service\Email;

use c975L\PaymentBundle\Entity\Payment;

/**
 * Interface to be called for DI for PurchaseCredits Email related services
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
interface PurchaseCreditsEmailInterface
{
    /**
     * Sends email for PurchaseCredits
     */
    public function send(Payment $payment, int $credits, $user);
}
