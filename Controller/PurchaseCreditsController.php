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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use c975L\Email\Service\EmailService;
use c975L\PaymentBundle\Entity\Payment;
use c975L\PaymentBundle\Service\PaymentService;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Entity\Transaction;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsType;

class PurchaseCreditsController extends Controller
{
//DASHBOARD
    /**
     * @Route("/purchase-credits/dashboard",
     *      name="purchasecredits_dashboard")
     * @Method({"GET", "HEAD"})
     */
    public function dashboardAction()
    {
        //Gets the user
        $user = $this->getUser();

        //Returns the dashboard
        if ($user !== null && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->render('@c975LPurchaseCredits/pages/dashboard.html.twig');
        }

        //Access is denied
        throw $this->createAccessDeniedException();
    }

//PURCHASE
    /**
     * @Route("/purchase-credits/{credits}",
     *      name="purchasecredits_purchase",
     *      defaults={"credits": "0"},
     *      requirements={"credits": "([0-9]+)"})
     * @Method({"GET", "HEAD", "POST"})
     */
    public function purchaseCreditsAction(Request $request, $credits)
    {
        //Gets the user
        $user = $this->getUser();

        if ($user !== null && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            //Defines prices
            $prices = array();
            foreach ($this->getParameter('c975_l_purchase_credits.creditsNumber') as $key => $value) {
                $prices[$value] = $this->getParameter('c975_l_purchase_credits.creditsPrice')[$key];
            }

            //Defines choices labels
            $translator = $this->get('translator');
            $pricesChoices = array();
            $creditReferencePrice = key($prices) / reset($prices);
            $fmt = new \NumberFormatter('en_EN' . '@currency=' . $this->getParameter('c975_l_purchase_credits.currency'), \NumberFormatter::CURRENCY);
            $currencySymbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            foreach ($prices as $key => $value) {
                //Calculates the discount (if one) based on the ratio of the first $price entry
                $discount = (1 - ($value / ($key / $creditReferencePrice))) * 100;
                $label = $key . ' ' . $translator->transChoice('label.credits', $key, array(), 'purchaseCredits') . ' - ' . $value . ' ' . $currencySymbol;
                $label = $discount != 0 ? $label . ' (-' . $discount . ' %)' : $label;
                $pricesChoices[$label] = $key;
            }

            //Gets the Terms of sales link
            $purchaseCreditsService = $this->get(\c975L\PurchaseCreditsBundle\Service\PurchaseCreditsService::class);
            $tosUrl = null;
            $tosUrlConfig = $this->getParameter('c975_l_purchase_credits.tosUrl');
            //Calculates the url if a Route is provided
            if (strpos($tosUrlConfig, ',') !== false) {
                $routeData = $purchaseCreditsService->getUrlFromRoute($tosUrlConfig);
                $tosUrl = $this->generateUrl($routeData['route'], $routeData['params'], UrlGeneratorInterface::ABSOLUTE_URL);
            //An url has been provided
            } elseif (strpos($tosUrlConfig, 'http') !== false) {
                $tosUrl = $tosUrlConfig;
            }

            //Defines form
            $credits = in_array($credits, $this->getParameter('c975_l_purchase_credits.creditsNumber')) === true ? (int) $credits : 0;
            $purchaseCredits = new PurchaseCredits($credits);
            $currency = $this->getParameter('c975_l_purchase_credits.currency');
            $form = $this->createForm(PurchaseCreditsType::class, $purchaseCredits, array('prices' => $pricesChoices));
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $credits = $form->getData()->getCredits();
                $cost = (int) ($prices[$credits] * 100);

                //Defines the payment
                $description = $translator->trans('label.purchase_credits', array(), 'purchaseCredits') . ' (' . $credits . ')';
                $paymentData = array(
                    'amount' => $cost,
                    'currency' => $currency,
                    'action' => json_encode(array('addCredits' => $credits)),
                    'description' => $description,
                    'userId' => $user->getId(),
                    'userIp' => $request->getClientIp(),
                    'live' => $this->getParameter('c975_l_purchase_credits.live'),
                    'returnRoute' => 'purchasecredits_payment_done',
                    'vat' => $this->getParameter('c975_l_purchase_credits.vat'),
                    );
                $paymentService = $this->get(\c975L\PaymentBundle\Service\PaymentService::class);
                $paymentService->create($paymentData);

                //Redirects to the payment
                return $this->redirectToRoute('payment_form');
            }

            return $this->render('@c975LPurchaseCredits/forms/purchase.html.twig', array(
                'form' => $form->createView(),
                'user' => $this->getUser(),
                'live' => $this->getParameter('c975_l_purchase_credits.live'),
                'tosUrl' => $tosUrl,
            ));
        }

        //Access is denied
        throw $this->createAccessDeniedException();
    }

//PAYMENT DONE
    /**
     * @Route("/purchase-credits/payment-done/{orderId}",
     *      name="purchasecredits_payment_done")
     * @Method({"GET", "HEAD"})
     */
    public function paymentDoneAction(Request $request, $orderId)
    {
        //Gets the manager
        $em = $this->getDoctrine()->getManager();

        //Gets payment
        $payment = $em->getRepository('c975L\PaymentBundle\Entity\Payment')
            ->findOneByOrderIdNotFinished($orderId);

        if ($payment instanceof Payment) {
            //Adds the credits
            $action = (array) json_decode($payment->getAction());
            if (array_key_exists('addCredits', $action)) {
                //Gets the user
                $user = $em->getRepository($this->getParameter('c975_l_purchase_credits.userEntity'))
                    ->findOneById($payment->getUserId());

                //Transaction
                $transactionService = $this->get(\c975L\PurchaseCreditsBundle\Service\TransactionService::class);
                $transaction = $transactionService->create('pmt' . $payment->getOrderId());
                $transaction
                    ->setCredits($action['addCredits'])
                    ->setDescription($payment->getDescription())
                ;
                $transactionService->persist($transaction, $user);

                //Set payment as finished
                $payment->setFinished(true);
                $em->persist($payment);

                //Persist in database
                $em->flush();

                //Gets the Terms of sales
                $translator = $this->get('translator');
                $tosPdfUrl = null;
                $tosPdfConfig = $this->getParameter('c975_l_purchase_credits.tosPdf');

                //Calculates the url if a Route is provided
                $purchaseCreditsService = $this->get(\c975L\PurchaseCreditsBundle\Service\PurchaseCreditsService::class);
                if (strpos($tosPdfConfig, ',') !== false) {
                    $routeData = $purchaseCreditsService->getUrlFromRoute($tosPdfConfig);
                    $tosPdfUrl = $this->generateUrl($routeData['route'], $routeData['params'], UrlGeneratorInterface::ABSOLUTE_URL);
                //An url has been provided
                } elseif (strpos($tosPdfConfig, 'http') !== false) {
                    $tosPdfUrl = $tosPdfConfig;
                }

                //Gets the content of TermsOfSales
                $tosPdfArray = null;
                if ($tosPdfUrl !== null) {
                    $tosPdfContent = file_get_contents($tosPdfUrl);
                    $filenameTos = $translator->trans('label.terms_of_sales_filename', array(), 'purchaseCredits') . '.pdf';
                    $tosPdfArray = array($tosPdfContent, $filenameTos, 'application/pdf');
                }

                //Sends email
                $subject = $translator->trans('label.purchased_credits', array('%credits%' => $action['addCredits']), 'purchaseCredits');
                $body = $this->renderView('@c975LPurchaseCredits/emails/purchase.html.twig', array(
                    'payment' => $payment,
                    'credits' => $action['addCredits'],
                    'userCredits' => $user->getCredits(),
                    'live' => $this->getParameter('c975_l_purchase_credits.live'),
                    ));
                $emailData = array(
                    'subject' => $subject,
                    'sentFrom' => $this->getParameter('c975_l_email.sentFrom'),
                    'sentTo' => $user->getEmail(),
                    'replyTo' => $this->getParameter('c975_l_email.sentFrom'),
                    'body' => $body,
                    'attach' => array(
                        $tosPdfArray,
                        ),
                    'ip' => $request->getClientIp(),
                    );
                $emailService = $this->get(\c975L\EmailBundle\Service\EmailService::class);
                $emailService->send($emailData, true);

                //Creates flash
                $flash = $translator->trans('text.credits_purchased', array('%credits%' => $action['addCredits']), 'purchaseCredits');
                $request->getSession()
                    ->getFlashBag()
                    ->add('success', $flash)
                    ;
            }
        }

        //Redirects to the display of payment
        return $this->redirectToRoute('payment_display', array(
            'orderId' => $orderId,
        ));
    }

//TRANSACTIONS
    /**
     * @Route("/purchase-credits/transactions",
     *      name="purchasecredits_transactions")
     * @Method({"GET", "HEAD"})
     */
    public function transactionsAction(Request $request)
    {
        //Gets the user
        $user = $this->getUser();

        if ($user !== null && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            //Gets the manager
            $em = $this->getDoctrine()->getManager();

            //Gets the repository
            $repository = $em->getRepository('c975L\PurchaseCreditsBundle\Entity\Transaction');

            //Gets the transactions
            $transactions = $repository->findByUserId($user->getId(), array('creation' => 'desc'));

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

//TRANSACTION DISPLAY
    /**
     * @Route("/purchase-credits/transaction/{orderId}",
     *      name="purchasecredits_transaction_display")
     * @Method({"GET", "HEAD"})
     */
    public function transactionDisplayAction(Request $request, $orderId)
    {
        //Gets the user
        $user = $this->getUser();

        if ($user !== null && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            //Gets the manager
            $em = $this->getDoctrine()->getManager();

            //Gets the repository
            $repository = $em->getRepository('c975L\PurchaseCreditsBundle\Entity\Transaction');

            //Gets the transaction
            $transaction = $repository->findOneByOrderId($orderId);

            if ($transaction instanceof Transaction) {
                if ($transaction->getUserId() == $user->getId()) {
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