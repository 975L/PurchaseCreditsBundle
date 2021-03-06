<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Controller;

use c975L\PurchaseCreditsBundle\Entity\Transaction;
use c975L\PurchaseCreditsBundle\Service\TransactionServiceInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Transaction Controller class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class TransactionController extends AbstractController
{
//ALL
    /**
     * Displays all the transactions
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/transactions",
     *    name="purchasecredits_transactions",
     *    methods={"HEAD", "GET"})
     */
    public function all(Request $request, PaginatorInterface $paginator, TransactionServiceInterface $transactionService)
    {
        $this->denyAccessUnlessGranted('c975LPurchaseCredits-all', null);

        //Renders the transactions
        $transactions = $paginator->paginate(
            $transactionService->getAll($this->getUser()),
            $request->query->getInt('p', 1),
            25
        );
        return $this->render(
            '@c975LPurchaseCredits/pages/transactions.html.twig',
            array(
                'transactions' => $transactions,
            ));
    }

//DISPLAY
    /**
     * Displays the specific Transaction using its orderId
     * @return Response
     *
     * @Route("/purchase-credits/transaction/{orderId}",
     *    name="purchasecredits_transaction_display",
     *    methods={"HEAD", "GET"})
     */
    public function display(Transaction $transaction)
    {
        $this->denyAccessUnlessGranted('c975LPurchaseCredits-display', $transaction);

        //Renders the transaction
        return $this->render(
            '@c975LPurchaseCredits/pages/transaction.html.twig',
            array(
                'transaction' => $transaction,
            ));
    }
}
