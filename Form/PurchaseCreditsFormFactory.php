<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Form;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * PurchaseCreditsFormFactory class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsFormFactory implements PurchaseCreditsFormFactoryInterface
{
    /**
     * Stores ConfigServiceInterface
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * Stores FormFactoryInterface
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(
        ConfigServiceInterface $configService,
        FormFactoryInterface $formFactory
    )
    {
        $this->configService = $configService;
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, PurchaseCredits $purchaseCredits, int $credits, array $priceChoices)
    {
        switch ($name) {
            case 'purchase':
                $config = array(
                    'credits' => in_array($credits, $this->configService->getParameter('c975LPurchaseCredits.creditsNumber')) ? (int) $credits : 0,
                    'pricesChoice' => $priceChoices,
                    'gdpr' => $this->configService->getParameter('c975LPurchaseCredits.gdpr'),
                    );
                break;
            default:
                $config = array();
                break;
        }

        return $this->formFactory->create(PurchaseCreditsType::class, $purchaseCredits, array('config' => $config));
    }
}
