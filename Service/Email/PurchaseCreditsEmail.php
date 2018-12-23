<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service\Email;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\EmailBundle\Service\EmailServiceInterface;
use c975L\PaymentBundle\Entity\Payment;
use c975L\ServicesBundle\Service\ServicePdfInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use Twig_Environment;

/**
 * Services related to PurchaseCredits Email
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsEmail implements PurchaseCreditsEmailInterface
{
    /**
     * Stores ConfigServiceInterface
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * Stores ContainerInterface
     * @var ContainerInterface
     */
    private $container;

    /**
     * Stores EmailServiceInterface
     * @var EmailServiceInterface
     */
    private $emailService;

    /**
     * Stores ServicePdfInterface
     * @var ServicePdfInterface
     */
    private $purchaseCreditsPdf;

    /**
     * Stores current Request
     * @var Request
     */
    private $request;

    /**
     * Stores Twig_Environment
     * @var Twig_Environment
     */
    private $templating;

    /**
     * Stores Translator
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ConfigServiceInterface $configService,
        EmailServiceInterface $emailService,
        ServicePdfInterface $servicePdf,
        RequestStack $requestStack,
        Twig_Environment $templating,
        TranslatorInterface $translator
    )
    {
        $this->configService = $configService;
        $this->emailService = $emailService;
        $this->servicePdf = $servicePdf;
        $this->request = $requestStack->getCurrentRequest();
        $this->templating = $templating;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Payment $payment, int $credits, $user)
    {
        //Gets the PDF for Terms of sales
        $tosPdf = $this->servicePdf->getPdfFile('label.terms_of_sales_filename', $this->configService->getParameter('c975LPurchaseCredits.tosPdf'));

        //Sends email
        $body = $this->templating->render('@c975LPurchaseCredits/emails/purchase.html.twig', array(
            'payment' => $payment,
            'credits' => $credits,
            'userCredits' => $user->getCredits(),
            'live' => $this->configService->getParameter('c975LPurchaseCredits.live'),
             '_locale' => $this->request->getLocale(),
            ));
        $emailData = array(
            'subject' => $this->translator->trans('label.purchased_credits', array('%credits%' => $credits), 'purchaseCredits'),
            'sentFrom' => $this->configService->getParameter('c975LEmail.sentFrom'),
            'sentTo' => $user->getEmail(),
            'replyTo' => $this->configService->getParameter('c975LEmail.sentFrom'),
            'body' => $body,
            'attach' => array($tosPdf),
            'ip' => $this->request->getClientIp(),
            );
        $this->emailService->send($emailData, true);
    }
}
