<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsType;
use c975L\PurchaseCreditsBundle\Form\PurchaseCreditsFormFactoryInterface;

/**
 * PurchaseCreditsFormFactory class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsFormFactory implements PurchaseCreditsFormFactoryInterface
{
    /**
     * Stores container
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        FormFactoryInterface $formFactory
    )
    {
        $this->container = $container;
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
                    'credits' => in_array($credits, $this->container->getParameter('c975_l_purchase_credits.creditsNumber')) ? (int) $credits : 0,
                    'pricesChoice' => $priceChoices,
                    'gdpr' => $this->container->getParameter('c975_l_purchase_credits.gdpr'),
                    );
                break;
            default:
                $config = array();
                break;
        }

        return $this->formFactory->create(PurchaseCreditsType::class, $purchaseCredits, array('config' => $config));
    }
}
