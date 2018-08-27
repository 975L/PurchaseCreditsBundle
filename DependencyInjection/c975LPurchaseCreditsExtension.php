<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * DI Extension Class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class c975LPurchaseCreditsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        $configuration = new Configuration();
        $processedConfig = $this->processConfiguration($configuration, $configs);

        $container->setParameter('c975_l_purchase_credits.creditsNumber', $processedConfig['creditsNumber']);
        $container->setParameter('c975_l_purchase_credits.creditsPrice', $processedConfig['creditsPrice']);
        $container->setParameter('c975_l_purchase_credits.gdpr', $processedConfig['gdpr']);
        $container->setParameter('c975_l_purchase_credits.currency', strtoupper($processedConfig['currency']));
        $container->setParameter('c975_l_purchase_credits.vat', $processedConfig['vat'] * 100);
        $container->setParameter('c975_l_purchase_credits.live', $processedConfig['live']);
        $container->setParameter('c975_l_purchase_credits.userEntity', $processedConfig['userEntity']);
        $container->setParameter('c975_l_purchase_credits.roleNeeded', $processedConfig['roleNeeded']);
        $container->setParameter('c975_l_purchase_credits.tosUrl', $processedConfig['tosUrl']);
        $container->setParameter('c975_l_purchase_credits.tosPdf', $processedConfig['tosPdf']);
    }
}
