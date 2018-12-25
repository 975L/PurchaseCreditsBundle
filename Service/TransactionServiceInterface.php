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
use c975L\PurchaseCreditsBundle\Entity\Transaction;

/**
 * Interface to be called for DI for TransactionServiceInterface related services
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
interface TransactionServiceInterface
{
    /**
     * Adds Transaction + User's credits
     */
    public function add(Payment $payment, $credits, $user);

    /**
     * Creates Transaction
     * @return Transaction
     */
    public function create($orderId = null);

    /**
     * Gets all the Transaction for the user
     * @return mixed
     */
    public function getAll($user);

    /**
     * Persists Transaction + User's data
     */
    public function persist(Transaction $transaction, $user);
}
