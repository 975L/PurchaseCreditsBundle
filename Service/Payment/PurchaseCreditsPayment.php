<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service\Payment;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use c975L\PaymentBundle\Service\PaymentServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Service\Payment\PurchaseCreditsPaymentInterface;

/**
 * Services related to PurchaseCredits Payment
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsPayment implements PurchaseCreditsPaymentInterface
{
    /**
     * Stores Container
     * @var ContainerInterface
     */
    private $container;

    /**
     * Stores PaymentService
     * @var PaymentServiceInterface
     */
    private $paymentService;

    /**
     * Stores Translator
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ContainerInterface $container,
        PaymentServiceInterface $paymentService,
        TranslatorInterface $translator
    )
    {
        $this->container = $container;
        $this->paymentService = $paymentService;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function payment(PurchaseCredits $purchaseCredits, $user)
    {
        $paymentData = array(
            'amount' => $purchaseCredits->getAmount(),
            'currency' => $purchaseCredits->getCurrency(),
            'action' => json_encode(array('addCredits' => $purchaseCredits->getCredits())),
            'description' => $this->translator->trans('label.purchase_credits', array(), 'purchaseCredits') . ' (' . $purchaseCredits->getCredits() . ')',
            'userId' => null !== $user ? $user->getId() : null,
            'userIp' => $purchaseCredits->getUserIp(),
            'live' => $this->container->getParameter('c975_l_purchase_credits.live'),
            'returnRoute' => 'purchasecredits_payment_done',
            'vat' => $this->container->getParameter('c975_l_purchase_credits.vat'),
            );

        $this->paymentService->create($paymentData);
    }
}