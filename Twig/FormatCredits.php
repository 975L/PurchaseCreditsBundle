<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Twig;

class FormatCredits extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'purchasecredits_format',
                array($this, 'formatCredits'),
                array('is_safe' => array('html'))
                ),
        );
    }

    public function formatCredits($amount)
    {
        if ($amount < 0) {
            return '<strong><span class="red">' . $amount . ' crd</span></strong>';
        } elseif ($amount > 0) {
            return '<strong><span class="green">+' . $amount . ' crd</span></strong>';
        }

        return '<strong> 0 crd</strong>';
    }
}