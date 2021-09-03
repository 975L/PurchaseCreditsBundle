<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Entity\Payment;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsFormFactoryInterface;
use c975L\PurchaseCreditsBundle\Service\Email\PurchaseCreditsEmailInterface;
use c975L\ServicesBundle\Service\ServiceToolsInterface;
use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PurchaseCreditsService class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2017 975L <contact@975l.com>
 */
class PurchaseCreditsService implements PurchaseCreditsServiceInterface
{
    /**
     * Stores ConfigServiceInterface
     * @var ConfigServiceInterface
     */
    private $configService;

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
     * @var Request
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
        ConfigServiceInterface $configService,
        EntityManagerInterface $em,
        PurchaseCreditsEmailInterface $purchaseCreditsEmail,
        PurchaseCreditsFormFactoryInterface $purchaseCreditsFormFactory,
        ServiceToolsInterface $serviceTools,
        RequestStack $requestStack,
        TransactionServiceInterface $transactionService,
        TranslatorInterface $translator
    )
    {
        $this->configService = $configService;
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
        $creditsNumber = $this->configService->getParameter('c975LPurchaseCredits.creditsNumber');
        $creditsPrice = $this->configService->getParameter('c975LPurchaseCredits.creditsPrice');

        if (is_array($creditsNumber) && is_array($creditsPrice)) {
            $prices = array();
            foreach ($creditsNumber as $key => $value) {
                $prices[(int) $value] = (int) $creditsPrice[$key];
            }
            return $prices;
        }

        throw new InvalidArgumentException('Either the parameter creditsNumber or creditsPrice is not set correctly');
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesChoice()
    {
        $prices = $this->getPrices();
        $pricesChoices = array();
        $creditReferencePrice = key($prices) / reset($prices);
        $format = new NumberFormatter('en_EN' . '@currency=' . $this->configService->getParameter('c975LPurchaseCredits.currency'), NumberFormatter::CURRENCY);
        $currencySymbol = $format->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        foreach ($prices as $key => $value) {
            //Calculates the discount (if one) based on the ratio of the first $price entry
            $discount = (1 - ($value / ($key / $creditReferencePrice))) * 100;
            $label = $key . ' ' . $this->translator->trans('label.credits', array('%count%' => $key), 'purchaseCredits') . ' - ' . $value . ' ' . $currencySymbol;
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
            ->setCurrency($this->configService->getParameter('c975LPurchaseCredits.currency'))
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
                ->getRepository($this->configService->getParameter('c975LPurchaseCredits.userEntity'))
                ->findOneById($payment->getUserId());

            //Adds Transaction + user's credits
            $this->transactionService->addPayment($payment, $action['addCredits'], $user);

            //Set payment as finished
            $payment->setFinished(true);
            $this->em->persist($payment);
            $this->em->flush();

            //Sends email
            $this->purchaseCreditsEmail->send($payment, $action['addCredits'], $user);

            //Creates flash
            $this->serviceTools->createFlash('purchaseCredits', 'text.credits_purchased', 'success', array('%credits%' => $action['addCredits']));

            return true;
        }

        return false;
    }
}
