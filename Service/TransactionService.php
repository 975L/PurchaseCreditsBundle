<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\PurchaseCreditsBundle\Entity\Transaction;

class TransactionService
{
    private $container;
    private $request;
    private $em;

    public function __construct(
        \Symfony\Component\DependencyInjection\ContainerInterface $container,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        \Doctrine\ORM\EntityManager $em
    )
    {
        $this->container = $container;
        $this->request = $requestStack->getCurrentRequest();
        $this->em = $em;
    }

    //Creates Transaction
    public function create($orderId = null)
    {
        $transaction = new Transaction();

        if ($orderId === null) {
            $now = \DateTime::createFromFormat('U.u', microtime(true));
            $orderId = $now->format('Ymd-His-u');
        }

        $transaction
            ->setOrderId($orderId)
            ->setCreation(new \DateTime())
            ->setUserIp($this->request->getClientIp())
        ;
    }

    //Persists Transaction + User's data
    public function persist($transaction, $user)
    {
        //Persists Transaction
        $transaction->setUserId($user->getId());
        $this->em->persist($transaction);

        //Adds/Subtracts credits to user
        if ($this->container->getParameter('c975_l_purchase_credits.live') === true) {
            //Credits are live on site
            if ($transaction->getCredits() < 0 ||
                //Payment is live on site
                $this->container->getParameter('c975_l_payment.live') === true ||
                //Transaction is not resulting from a test payment
                substr($transaction->getOrderId(), 0,3) != 'pmt') {
                $user->addCredits($transaction->getCredits());
                $em->persist($user);
            }
        }
    }
}