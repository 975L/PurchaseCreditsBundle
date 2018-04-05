<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Twig;

class PurchaseCreditsDivData extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'purchasecredits_divData',
                array($this, 'divData'),
                array(
                    'needs_environment' => true,
                    'is_safe' => array('html'),
                )
            ),
        );
    }

    public function divData(\Twig_Environment $environment)
    {
        $render = $environment->render('@c975LPurchaseCredits/fragments/divData.html.twig');

        return str_replace(array("\n", '    ', '   ', '  '), ' ', $render);
    }
}