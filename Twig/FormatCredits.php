<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Twig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to display the formatted number of credits using `|purchasecredits_format`
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class FormatCredits extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter(
                'purchasecredits_format',
                array($this, 'formatCredits'),
                array('is_safe' => array('html'))
                ),
        );
    }

    /**
     * Returns the number of credits formatted
     * @return string
     */
    public function formatCredits($amount)
    {
        if ($amount < 0) {
            return '<strong><span class="red">' . $amount . ' crd</span></strong>';
        } elseif ($amount > 0) {
            return '<strong><span class="green">+' . $amount . ' crd</span></strong>';
        }

        return '<strong>0 crd</strong>';
    }
}
