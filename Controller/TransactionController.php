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
use c975L\PurchaseCreditsBundle\Entity\Transaction;

class TransactionController extends Controller
{
//LIST
    /**
     * @Route("/purchase-credits/transactions",
     *      name="purchasecredits_transactions")
     * @Method({"GET", "HEAD"})
     */
    public function list(Request $request)
    {
        if (null !== $this->getUser() && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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

            //Renders the page
            return $this->render('@c975LPurchaseCredits/pages/transactions.html.twig', array(
                'transactions' => $pagination,
                ));
        }

        //Access is denied
        throw $this->createAccessDeniedException();
    }

//DISPLAY
    /**
     * @Route("/purchase-credits/transaction/{orderId}",
     *      name="purchasecredits_transaction_display")
     * @Method({"GET", "HEAD"})
     */
    public function display($orderId)
    {
        //Gets the user
        $user = $this->getUser();

        if ($this->getUser() !== null && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            //Gets the transaction
            $transaction = $this->getDoctrine()
                ->getManager()
                ->getRepository('c975L\PurchaseCreditsBundle\Entity\Transaction')
                ->findOneByOrderId($orderId);

            if ($transaction instanceof Transaction) {
                if ($transaction->getUserId() == $this->getUser()->getId()) {
                    //Renders the page
                    return $this->render('@c975LPurchaseCredits/pages/transaction.html.twig', array(
                        'transaction' => $transaction,
                        ));
                }

                //Access is denied
                throw $this->createAccessDeniedException();
            }

            //Not found
            throw $this->createNotFoundException();
        }

        //Access is denied
        throw $this->createAccessDeniedException();
    }
}
