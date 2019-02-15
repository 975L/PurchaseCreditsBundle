<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity Transaction (linked to DB table `user_transactions`)
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2017 975L <contact@975l.com>
 *
 * @ORM\Table(name="user_transactions")
 * @ORM\Entity
 */
class Transaction
{
    /**
     * Transaction unique id
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * OrdreId for the Transaction
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=48, nullable=true)
     */
    protected $orderId;

    /**
     * Credits added or subtracted
     * @var int
     *
     * @ORM\Column(name="credits", type="integer", nullable=true)
     */
    protected $credits;

    /**
     * Description for the Transaction
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    protected $description;

    /**
     * User unique id
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    protected $userId;

    /**
     * User IP address
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=48, nullable=true)
     */
    protected $userIp;

    /**
     * DateTime creation for the Transaction
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime", nullable=true)
     */
    protected $creation;

    /**
     * Get id
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set orderId
     * @param string
     * @return Transaction
     */
    public function setOrderId(?string $orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     * @return string
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * Set credits
     * @param int
     * @return Transaction
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
    public function getCredits(): int
    {
        return $this->credits;
    }

    /**
     * Set description
     * @param string
     * @return Transaction
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set userId
     * @param int
     * @return Transaction
     */
    public function setUserId(?int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
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

    /**
     * Set creation
     * @param DateTime
     * @return Transaction
     */
    public function setCreation(?DateTime $creation)
    {
        $this->creation = $creation;

        return $this;
    }

    /**
     * Get creation
     * @return DateTime
     */
    public function getCreation(): DateTime
    {
        return $this->creation;
    }
}
