<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use c975L\PaymentBundle\Entity\Payment;
use c975L\ServicesBundle\Service\ServiceToolsInterface;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsFormFactoryInterface;
use c975L\PurchaseCreditsBundle\Service\TransactionServiceInterface;
use c975L\PurchaseCreditsBundle\Service\PurchaseCreditsServiceInterface;
use c975L\PurchaseCreditsBundle\Service\Email\PurchaseCreditsEmailInterface;

/**
 * PurchaseCreditsService class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2017 975L <contact@975l.com>
 */
class PurchaseCreditsService implements PurchaseCreditsServiceInterface
{
    /**
     * Stores ContainerInterface
     * @var ContainerInterface
     */
    private $container;

    /**
     * Stores EntityManagerInterface
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Stores PurchaseCreditsEmailInterface
     * @var PurchaseCreditsEmailInterface
     */
    private $purchaseCreditsEmail;

    /**
     * Stores PurchaseCreditsFormFactoryInterface
     * @var PurchaseCreditsFormFactoryInterface
     */
    private $purchaseCreditsFormFactory;

    /**
     * Stores ServiceToolsInterface
     * @var ServiceToolsInterface
     */
    private $serviceTools;

    /**
     * Stores current Request
     * @var RequestStack
     */
    private $request;

    /**
     * Stores TransactionServiceInterface
     * @var TransactionServiceInterface
     */
    private $transactionService;

    /**
     * Stores TranslatorInterface
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $em,
        PurchaseCreditsEmailInterface $purchaseCreditsEmail,
        PurchaseCreditsFormFactoryInterface $purchaseCreditsFormFactory,
        ServiceToolsInterface $serviceTools,
        RequestStack $requestStack,
        TransactionServiceInterface $transactionService,
        TranslatorInterface $translator
    )
    {
        $this->container = $container;
        $this->em = $em;
        $this->purchaseCreditsEmail = $purchaseCreditsEmail;
        $this->purchaseCreditsFormFactory = $purchaseCreditsFormFactory;
        $this->serviceTools = $serviceTools;
        $this->request = $requestStack->getCurrentRequest();
        $this->transactionService = $transactionService;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $purchaseCredits = new PurchaseCredits();
        $purchaseCredits
            ->setUserIp($this->request->getClientIp())
        ;

        return $purchaseCredits;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm(string $name, PurchaseCredits $purchaseCredits, int $credits, array $priceChoices)
    {
        return $this->purchaseCreditsFormFactory->create($name, $purchaseCredits, $credits, $priceChoices);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrices()
    {
        $prices = array();
        foreach ($this->container->getParameter('c975_l_purchase_credits.creditsNumber') as $key => $value) {
            $prices[$value] = $this->container->getParameter('c975_l_purchase_credits.creditsPrice')[$key];
        }

        return $prices;
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesChoice()
    {
        $prices = $this->getPrices();
        $pricesChoices = array();
        $creditReferencePrice = key($prices) / reset($prices);
        $format = new \NumberFormatter('en_EN' . '@currency=' . $this->container->getParameter('c975_l_purchase_credits.currency'), \NumberFormatter::CURRENCY);
        $currencySymbol = $format->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        foreach ($prices as $key => $value) {
            //Calculates the discount (if one) based on the ratio of the first $price entry
            $discount = (1 - ($value / ($key / $creditReferencePrice))) * 100;
            $label = $key . ' ' . $this->translator->transChoice('label.credits', $key, array(), 'purchaseCredits') . ' - ' . $value . ' ' . $currencySymbol;
            $label = $discount != 0 ? $label . ' (-' . $discount . ' %)' : $label;
            $pricesChoices[$label] = $key;
        }

        return $pricesChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function define(PurchaseCredits $purchaseCredits)
    {
        $purchaseCredits
            ->setAmount($this->getPrices()[$purchaseCredits->getCredits()] * 100)
            ->setCurrency($this->container->getParameter('c975_l_purchase_credits.currency'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Payment $payment)
    {
        $action = (array) json_decode($payment->getAction());

        if (array_key_exists('addCredits', $action)) {
            //Gets the user
            $user = $this->em
                ->getRepository($this->container->getParameter('c975_l_purchase_credits.userEntity'))
                ->findOneById($payment->getUserId());

            //Adds Transaction + user's credits
            $this->transactionService->add($payment, $action['addCredits'], $user);

            //Set payment as finished
            $payment->setFinished(true);
            $this->em->persist($payment);
            $this->em->flush();

            //Sends email
            $this->purchaseCreditsEmail->send($payment, $action['addCredits'], $user);

            //Creates flash
            $this->serviceTools->createFlash('purchasedCredits', 'text.credits_purchased', 'success', array('%credits%' => $action['addCredits']));

            return true;
        }

        return false;
    }
}
