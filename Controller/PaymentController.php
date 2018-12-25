<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Controller;

use c975L\PaymentBundle\Entity\Payment;
use c975L\PaymentBundle\Service\PaymentServiceInterface;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Payment Controller class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PaymentController extends Controller
{
//PAYMENT DONE

    /**
     * Return Route after having done payment
     * @return Redirect
     * @throws NotFoundHttpException
     *
     * @Route("/purchase-credits/payment-done/{orderId}",
     *      name="purchasecredits_payment_done")
     * @Method({"GET", "HEAD"})
     */
    public function paymentDone(PurchaseCreditsServiceInterface $purchaseCreditsService, PaymentServiceInterface $paymentService, Payment $payment)
    {
        $validation = $purchaseCreditsService->validate($payment);

        //Redirects to the display of payment
        if ($validation) {
            return $this->redirectToRoute('payment_display', array(
                'orderId' => $payment->getOrderId(),
            ));
        }

        //Payment has been done but Credits were not added
        $paymentService->error($payment);

        return $this->redirectToRoute('payment_display', array('orderId' => $payment->getOrderId()));
    }
}
