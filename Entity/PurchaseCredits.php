<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Entity;

class PurchaseCredits
{
    protected $credits;
    protected $amount;
    protected $currency;

    public function setCredits($credits)
    {
        $this->credits = $credits;
    }
    public function getCredits()
    {
        return $this->credits;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }
    public function getAmount()
    {
        return $this->amount;
    }

    public function setCurrency($currency)
    {
        $this->currency = strtoupper($currency);
    }
    public function getCurrency()
    {
        return strtoupper($this->currency);
    }
}
