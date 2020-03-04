<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Controller;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Service\Payment\PurchaseCreditsPaymentInterface;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsServiceInterface;
use c975L\ServicesBundle\Service\ServiceToolsInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
/**
 * PurchaseCredits Controller class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsController extends AbstractController
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
    ) {
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
     *    name="purchasecredits_dashboard",
     *    methods={"HEAD", "GET"})
     */
    public function dashboard()
    {
        $this->denyAccessUnlessGranted('c975LPurchaseCredits-dashboard', null);

        //Renders the dashboard
        return $this->render('@c975LPurchaseCredits/pages/dashboard.html.twig');
    }

//CONFIG
    /**
     * Displays the configuration
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/config",
     *    name="purchasecredits_config",
     *    methods={"HEAD", "GET", "POST"})
     */
    public function config(Request $request, ConfigServiceInterface $configService)
    {
        $this->denyAccessUnlessGranted('c975LPurchaseCredits-config', null);

        //Defines form
        $form = $configService->createForm('c975l/purchasecredits-bundle');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Validates config
            $configService->setConfig($form);

            //Redirects
            return $this->redirectToRoute('purchasecredits_dashboard');
        }

        //Renders the config form
        return $this->render(
            '@c975LConfig/forms/config.html.twig',
            array(
                'form' => $form->createView(),
                'toolbar' => '@c975LPurchaseCredits',
            ));
    }

//PURCHASE CREDITS
    /**
     * Displays the form to purchase credits
     * @return Response
     * @throws AccessDeniedException
     *
     * @Route("/purchase-credits/{credits}",
     *    name="purchasecredits_purchase",
     *    defaults={"credits": "0"},
     *    requirements={"credits": "([0-9]+)"},
     *    methods={"HEAD", "GET", "POST"})
     */
    public function purchaseCredits(Request $request, ConfigServiceInterface $configService, $credits)
    {
        $purchaseCredits = $this->purchaseCreditsService->create();
        $this->denyAccessUnlessGranted('c975LPurchaseCredits-purchase', $purchaseCredits);

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
        return $this->render(
            '@c975LPurchaseCredits/forms/purchase.html.twig',
            array(
                'form' => $form->createView(),
                'user' => $this->getUser(),
                'live' => $configService->getParameter('c975LPurchaseCredits.live') && $configService->getParameter('c975LPayment.live'),
                'tosUrl' => $this->serviceTools->getUrl($configService->getParameter('c975LPurchaseCredits.tosUrl')),
            ));
    }
}
