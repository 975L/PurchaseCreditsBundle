<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PurchaseCreditsService
{
    private $container;
    private $em;
    private $emailService;
    private $paymentService;
    private $request;
    private $router;
    private $templating;
    private $transactionService;

    public function __construct(
        \Symfony\Component\DependencyInjection\ContainerInterface $container,
        \Doctrine\ORM\EntityManager $em,
        \c975L\EmailBundle\Service\EmailService $emailService,
        \c975L\PaymentBundle\Service\PaymentService $paymentService,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        \Symfony\Component\Routing\Generator\UrlGeneratorInterface $router,
        \Twig_Environment $templating,
        \c975L\PurchaseCreditsBundle\Service\TransactionService $transactionService
    ) {
        $this->container = $container;
        $this->em = $em;
        $this->emailService = $emailService;
        $this->paymentService = $paymentService;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->templating = $templating;
        $this->transactionService = $transactionService;
    }

    //Adds credits
    public function add($payment)
    {
        $action = (array) json_decode($payment->getAction());
        if (array_key_exists('addCredits', $action)) {
            //Gets the user
            $user = $this->em
                ->getRepository($this->container->getParameter('c975_l_purchase_credits.userEntity'))
                ->findOneById($payment->getUserId());

            //Adds Transaction
            $this->transactionService->add($payment, $action['addCredits'], $user);

            //Set payment as finished
            $payment->setFinished(true);
            $this->em->persist($payment);
            $this->em->flush();

            //Sends email
            $this->sendEmail($payment, $action['addCredits'], $user);

            //Creates flash
            $flash = $this->container->get('translator')->trans('text.credits_purchased', array('%credits%' => $action['addCredits']), 'purchaseCredits');
            $this->request->getSession()
                ->getFlashBag()
                ->add('success', $flash)
                ;

            return true;
        }

        return false;
    }

    //Gets prices for credits
    public function getPrices()
    {
        $prices = array();
        foreach ($this->container->getParameter('c975_l_purchase_credits.creditsNumber') as $key => $value) {
            $prices[$value] = $this->container->getParameter('c975_l_purchase_credits.creditsPrice')[$key];
        }

        return $prices;
    }

    //Gets prices choices labels for credits
    public function getPricesChoices($prices)
    {
        //Defines choices labels
        $translator = $this->container->get('translator');
        $pricesChoices = array();
        $creditReferencePrice = key($prices) / reset($prices);
        $format = new \NumberFormatter('en_EN' . '@currency=' . $this->container->getParameter('c975_l_purchase_credits.currency'), \NumberFormatter::CURRENCY);
        $currencySymbol = $format->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        foreach ($prices as $key => $value) {
            //Calculates the discount (if one) based on the ratio of the first $price entry
            $discount = (1 - ($value / ($key / $creditReferencePrice))) * 100;
            $label = $key . ' ' . $translator->transChoice('label.credits', $key, array(), 'purchaseCredits') . ' - ' . $value . ' ' . $currencySymbol;
            $label = $discount != 0 ? $label . ' (-' . $discount . ' %)' : $label;
            $pricesChoices[$label] = $key;
        }

        return $pricesChoices;
    }

    //Gets the Terms of sales url
    public function getTosUrl()
    {
        return $this->getUrl($this->container->getParameter('c975_l_purchase_credits.tosUrl'));
    }

    //Gets the Terms of sales PDF
    public function getTosPdf()
    {
        $tosPdfUrl = $this->getUrl($this->container->getParameter('c975_l_purchase_credits.tosPdf'));

        //Gets the content of TermsOfSales PDF
        if ($tosPdfUrl !== null) {
            $tosPdf = file_get_contents($tosPdfUrl);
            $filenameTos = $this->container->get('translator')->trans('label.terms_of_sales_filename', array(), 'purchaseCredits') . '.pdf';
            return array($tosPdf, $filenameTos, 'application/pdf');
        }

        return null;
    }

    //Defines the url
    public function getUrl($data)
    {
        //Calculates the url if a Route is provided
        if (false !== strpos($data, ',')) {
            $routeData = $this->getUrlFromRoute($data);
            $url = $this->router->generate($routeData['route'], $routeData['params'], UrlGeneratorInterface::ABSOLUTE_URL);
        //An url has been provided
        } elseif (false !== strpos($data, 'http')) {
            $url = $data;
        //Not valid data
        } else {
            $url = null;
        }

        return $url;
    }

    //Gets url from a Route
    public function getUrlFromRoute($route)
    {
        //Gets Route
        $routeValue = trim(substr($route, 0, strpos($route, ',')), "\'\"");

        //Gets parameters
        $params = trim(substr($route, strpos($route, '{')), "{}");
        $params = str_replace(array('"', "'"), '', $params);
        $params = explode(',', $params);

        //Caculates url
        $paramsArray = array();
        foreach($params as $value) {
            $parameter = explode(':', $value);
            $paramsArray[trim($parameter[0])] = trim($parameter[1]);
        }

        return array(
            'route' => $routeValue,
            'params' => $paramsArray
        );
    }

    //Defines payment for Credits purchased
    public function payment($purchaseCredits, $userId)
    {
        $paymentData = array(
            'amount' => $purchaseCredits->getAmount(),
            'currency' => $purchaseCredits->getCurrency(),
            'action' => json_encode(array('addCredits' => $purchaseCredits->getCredits())),
            'description' => $this->container->get('translator')->trans('label.purchase_credits', array(), 'purchaseCredits') . ' (' . $purchaseCredits->getCredits() . ')',
            'userId' => $userId,
            'userIp' => $this->request->getClientIp(),
            'live' => $this->container->getParameter('c975_l_purchase_credits.live'),
            'returnRoute' => 'purchasecredits_payment_done',
            'vat' => $this->container->getParameter('c975_l_purchase_credits.vat'),
            );
        $this->paymentService->create($paymentData);
    }

    //Sends email for GiftVoucher purchased
    public function sendEmail($payment, $credits, $user)
    {
        //Gets the PDF of Terms of sales
        $tosPdf = $this->getTosPdf();

        //Sends email
        $body = $this->templating->render('@c975LPurchaseCredits/emails/purchase.html.twig', array(
            'payment' => $payment,
            'credits' => $credits,
            'userCredits' => $user->getCredits(),
            'live' => $this->container->getParameter('c975_l_purchase_credits.live'),
             '_locale' => $this->request->getLocale(),
            ));
        $emailData = array(
            'subject' => $this->container->get('translator')->trans('label.purchased_credits', array('%credits%' => $credits), 'purchaseCredits'),
            'sentFrom' => $this->container->getParameter('c975_l_email.sentFrom'),
            'sentTo' => $user->getEmail(),
            'replyTo' => $this->container->getParameter('c975_l_email.sentFrom'),
            'body' => $body,
            'attach' => array($tosPdf),
            'ip' => $this->request->getClientIp(),
            );
        $this->emailService->send($emailData, true);
    }
}
