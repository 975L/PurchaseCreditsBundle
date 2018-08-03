<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use c975L\PurchaseCreditsBundle\Entity\Transaction;

class TransactionController extends Controller
{
//ALL
    /**
     * @Route("/purchase-credits/transactions",
     *      name="purchasecredits_transactions")
     * @Method({"GET", "HEAD"})
     */
    public function all(Request $request)
    {
        $this->denyAccessUnlessGranted('all', null);

        //Gets the transactions
        $transactions = $this->getDoctrine()
            ->getManager()
            ->getRepository('c975L\PurchaseCreditsBundle\Entity\Transaction')
            ->findByUserId($this->getUser()->getId(), array('creation' => 'desc'));

        //Pagination
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $transactions,
            $request->query->getInt('p', 1),
            25
        );

        //Renders the transactions
        return $this->render('@c975LPurchaseCredits/pages/transactions.html.twig', array(
            'transactions' => $pagination,
        ));
    }

//DISPLAY
    /**
     * @Route("/purchase-credits/transaction/{orderId}",
     *      name="purchasecredits_transaction_display")
     * @Method({"GET", "HEAD"})
     */
    public function display(Transaction $transaction)
    {
        $this->denyAccessUnlessGranted('display', $transaction);

        //Renders the transaction
        return $this->render('@c975LPurchaseCredits/pages/transaction.html.twig', array(
            'transaction' => $transaction,
        ));
    }
}
