<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Entity;

/**
 * Entity PurchaseCredits (not linked to DB)
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2017 975L <contact@975l.com>
 */
class PurchaseCredits
{
    /**
     * Number of PurchasedCredits
     * @var int
     */
    protected $credits;

    /**
     * Amount in cents for the PurchasedCredits
     * @var int
     */
    protected $amount;

    /**
     * Currency for the amount paid for PurchasedCredits
     * @var int
     */
    protected $currency;

    /**
     * User IP address
     * @var string
     */
    protected $userIp;

    /**
     * Set credits
     * @param int
     * @return PurchaseCredits
     */
    public function setCredits(?int $credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits
     * @return int
     */
    public function getCredits(): ?int
    {
        return $this->credits;
    }

    /**
     * Set amount
     * @param int
     * @return PurchaseCredits
     */
    public function setAmount(?int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * Set currency
     * @param string
     * @return PurchaseCredits
     */
    public function setCurrency(?string $currency)
    {
        $this->currency = strtoupper($currency);

        return $this;
    }

    /**
     * Get currency
     * @return string
     */
    public function getCurrency(): ?string
    {
        return strtoupper($this->currency);
    }

    /**
     * Set userIp
     * @param string
     * @return Transaction
     */
    public function setUserIp(?string $userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     * @return string
     */
    public function getUserIp(): ?string
    {
        return $this->userIp;
    }
}
