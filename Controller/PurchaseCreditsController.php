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
use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsType;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsService;

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
        $this->denyAccessUnlessGranted('dashboard', null);

        //Renders the dashboard
        return $this->render('@c975LPurchaseCredits/pages/dashboard.html.twig');
    }

//PURCHASE
    /**
     * @Route("/purchase-credits/{credits}",
     *      name="purchasecredits_purchase",
     *      defaults={"credits": "0"},
     *      requirements={"credits": "([0-9]+)"})
     * @Method({"GET", "HEAD", "POST"})
     */
    public function purchaseCreditsAction(Request $request, PurchaseCreditsService $purchaseCreditsService, $credits)
    {
        $purchaseCredits = new PurchaseCredits();
        $this->denyAccessUnlessGranted('purchase', $purchaseCredits);

        //Defines prices
        $prices = $purchaseCreditsService->getPrices();
        $pricesChoices = $purchaseCreditsService->getPricesChoices($prices);

        //Defines form
        $credits = in_array($credits, $this->getParameter('c975_l_purchase_credits.creditsNumber')) === true ? (int) $credits : 0;

        $form = $this->createForm(PurchaseCreditsType::class, $purchaseCredits, array('prices' => $pricesChoices));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $purchaseCredits->setCredits($form->getData()->getCredits());
            $purchaseCredits->setAmount($prices[$form->getData()->getCredits()] * 100);
            $purchaseCredits->setCurrency($this->getParameter('c975_l_purchase_credits.currency'));

            //Redirects to the payment
            $userId = null !== $this->getUser() ? $this->getUser()->getId() : null;
            $purchaseCreditsService->payment($purchaseCredits, $userId);

            return $this->redirectToRoute('payment_form');
        }

        //Renders the purchase credits page
        return $this->render('@c975LPurchaseCredits/forms/purchase.html.twig', array(
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'live' => $this->getParameter('c975_l_purchase_credits.live'),
            'tosUrl' => $purchaseCreditsService->getTosUrl(),
        ));
    }

//PAYMENT DONE
    /**
     * @Route("/purchase-credits/payment-done/{orderId}",
     *      name="purchasecredits_payment_done")
     * @Method({"GET", "HEAD"})
     */
    public function paymentDoneAction(PurchaseCreditsService $purchaseCreditsService, $orderId)
    {
        //Gets Stripe payment not finished
        $payment = $this->getDoctrine()
            ->getManager()
            ->getRepository('c975L\PaymentBundle\Entity\Payment')
            ->findOneByOrderIdNotFinished($orderId);

        //Adds the credits
        if ($payment instanceof Payment) {
            $purchaseCreditsService->add($payment);
        }

        //Redirects to the display of payment
        return $this->redirectToRoute('payment_display', array(
            'orderId' => $orderId,
        ));
    }
}
