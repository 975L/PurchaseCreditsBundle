<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;

/**
 * Interface to be called for DI for PurchaseCreditsServiceInterface related services
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
interface PurchaseCreditsServiceInterface
{
    /**
     * Creates the PurchaseCredits
     * @return PurchaseCredits
     */
    public function create();

    /**
     * Gets prices for credits
     * @return array
     */
    public function getPrices();

    /**
     * Gets prices choice labels for credits
     * @return array
     */
    public function getPricesChoice();

    /**
     * Registers the PurchaseCredits
     */
    public function define(PurchaseCredits $purchaseCredits);
    /**
     * Validate the credits purchased
     * @return bool
     */
    public function validate(Payment $payment);
}
