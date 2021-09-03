<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service\Payment;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Service\PaymentServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Services related to PurchaseCredits Payment
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsPayment implements PurchaseCreditsPaymentInterface
{
    /**
     * Stores ConfigServiceInterface
     * @var ConfigServiceInterface
     */
    private $configService;

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
        ConfigServiceInterface $configService,
        PaymentServiceInterface $paymentService,
        TranslatorInterface $translator
    )
    {
        $this->configService = $configService;
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
            'live' => $this->configService->getParameter('c975LPurchaseCredits.live'),
            'returnRoute' => 'purchasecredits_payment_done',
            'vat' => $this->configService->getParameter('c975LPurchaseCredits.vat'),
            );

        $this->paymentService->create($paymentData);
    }
}