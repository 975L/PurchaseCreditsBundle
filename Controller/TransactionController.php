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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Knp\Component\Pager\PaginatorInterface;
use c975L\PurchaseCreditsBundle\Entity\Transaction;
use c975L\PurchaseCreditsBundle\Service\TransactionServiceInterface;

/**
 * Transaction Controller class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class TransactionController extends Controller
{
//ALL
    /**
     * Displays all the transactions
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/transactions",
     *      name="purchasecredits_transactions")
     * @Method({"GET", "HEAD"})
     */
    public function all(Request $request, PaginatorInterface $paginator, TransactionServiceInterface $transactionService)
    {
        $this->denyAccessUnlessGranted('all', null);

        //Renders the transactions
        $transactions = $paginator->paginate(
            $transactionService->getAll($this->getUser()),
            $request->query->getInt('p', 1),
            25
        );
        return $this->render('@c975LPurchaseCredits/pages/transactions.html.twig', array(
            'transactions' => $transactions,
        ));
    }

//DISPLAY
    /**
     * Displays the specific Transaction using its orderId
     * @return Response
     *
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
