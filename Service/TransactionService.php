<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\Transaction;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * TransactionService class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2017 975L <contact@975l.com>
 */
class TransactionService implements TransactionServiceInterface
{
    /**
     * Stores ConfigServiceInterface
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * Stores EntityManagerInterface
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Stores current Request
     * @var Request
     */
    private $request;

    public function __construct(
        ConfigServiceInterface $configService,
        EntityManagerInterface $em,
        RequestStack $requestStack
    )
    {
        $this->configService = $configService;
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function add(Payment $payment, $credits, $user)
    {
        $transaction = $this->create('pmt' . $payment->getOrderId());
        $transaction
            ->setCredits($credits)
            ->setDescription($payment->getDescription())
        ;

        $this->persist($transaction, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function create($orderId = null)
    {
        $transaction = new Transaction();

        if (null === $orderId) {
            $now = DateTime::createFromFormat('U.u', microtime(true));
            $orderId = $now->format('Ymd-His-u');
        }

        $transaction
            ->setOrderId($orderId)
            ->setCreation(new DateTime())
            ->setUserIp($this->request->getClientIp())
        ;

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($user)
    {
        return $this->em
            ->getRepository('c975L\PurchaseCreditsBundle\Entity\Transaction')
            ->findByUserId($user->getId(), array('creation' => 'desc'));
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Transaction $transaction, $user)
    {
        //Persists Transaction
        $transaction->setUserId($user->getId());
        $this->em->persist($transaction);

        //Adds/Subtracts credits to user
        if (
            //Credits are live on site
            $this->configService->getParameter('c975LPurchaseCredits.live') &&
            //AND Method addCredits() exists on user class
            method_exists(get_class($user), 'addCredits') &&
            //AND Credits are used by user
            (($transaction->getCredits() < 0 ||
            //OR Payment is live on site
            $this->configService->getParameter('c975LPayment.live') ||
            //OR Transaction is not resulting from a test payment
            substr($transaction->getOrderId(), 0, 3) != 'pmt'))
        ) {
                $user->addCredits($transaction->getCredits());
                $this->em->persist($user);
        }
    }
}
