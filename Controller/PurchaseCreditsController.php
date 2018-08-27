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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsServiceInterface;
use c975L\PurchaseCreditsBundle\Service\Payment\PurchaseCreditsPaymentInterface;
use c975L\ServicesBundle\Service\ServiceToolsInterface;
/**
 * PurchaseCredits Controller class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsController extends Controller
{
    /**
     * Stores PurchaseCreditsPaymentInterface
     * @var PurchaseCreditsPaymentInterface
     */
    private $purchaseCreditsPayment;

    /**
     * Stores PurchaseCreditsServiceInterface
     * @var PurchaseCreditsServiceInterface
     */
    private $purchaseCreditsService;

    /**
     * Stores ServiceToolsInterface
     * @var ServiceToolsInterface
     */
    private $serviceTools;

    public function __construct(
        PurchaseCreditsPaymentInterface $purchaseCreditsPayment,
        PurchaseCreditsServiceInterface $purchaseCreditsService,
        ServiceToolsInterface $serviceTools
    )
    {
        $this->purchaseCreditsPayment = $purchaseCreditsPayment;
        $this->purchaseCreditsService = $purchaseCreditsService;
        $this->serviceTools = $serviceTools;
    }

//DASHBOARD
    /**
     * Displays the dashboard for PurchaseCredits
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/dashboard",
     *      name="purchasecredits_dashboard")
     * @Method({"GET", "HEAD"})
     */
    public function dashboard()
    {
        $this->denyAccessUnlessGranted('dashboard', null);

        //Renders the dashboard
        return $this->render('@c975LPurchaseCredits/pages/dashboard.html.twig');
    }

//PURCHASE CREDITS
    /**
     * Displays the form to purchase credits
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/{credits}",
     *      name="purchasecredits_purchase",
     *      defaults={"credits": "0"},
     *      requirements={"credits": "([0-9]+)"})
     * @Method({"GET", "HEAD", "POST"})
     */
    public function purchaseCredits(Request $request, $credits)
    {
        $purchaseCredits = $this->purchaseCreditsService->create();
        $this->denyAccessUnlessGranted('purchase', $purchaseCredits);

        //Defines form
        $form = $this->purchaseCreditsService->createForm('purchase', $purchaseCredits, $credits, $this->purchaseCreditsService->getPricesChoice());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Defines the PurchaseCredits
            $this->purchaseCreditsService->define($purchaseCredits);

            //Redirects to the payment
            $this->purchaseCreditsPayment->payment($purchaseCredits, $this->getUser());
            return $this->redirectToRoute('payment_form');
        }

        //Renders the purchase credits form
        return $this->render('@c975LPurchaseCredits/forms/purchase.html.twig', array(
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'live' => $this->getParameter('c975_l_purchase_credits.live'),
            'tosUrl' => $this->serviceTools->getUrl($this->getParameter('c975_l_purchase_credits.tosUrl')),
        ));
    }
}
