<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Security;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use c975L\PurchaseCreditsBundle\Entity\Transaction;

/**
 * Voter for Transaction access
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class TransactionVoter extends Voter
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * The role needed to be allowed access (defined in config)
     * @var string
     */
    private $roleNeeded;

    /**
     * Used for access to all
     * @var string
     */
    public const ALL = 'all';

    /**
     * Used for access to display
     * @var string
     */
    public const DISPLAY = 'display';

    /**
     * Contains all the available attributes to check with in supports()
     * @var array
     */
    private const ATTRIBUTES = array(
        self::ALL,
        self::DISPLAY,
    );

    public function __construct(AccessDecisionManagerInterface $decisionManager, string $roleNeeded)
    {
        $this->decisionManager = $decisionManager;
        $this->roleNeeded = $roleNeeded;
    }

    /**
     * Checks if attribute and subject are supported
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (null !== $subject) {
            return $subject instanceof Transaction && in_array($attribute, self::ATTRIBUTES);
        }

        return in_array($attribute, self::ATTRIBUTES);
    }

    /**
     * Votes if access is granted
     * @return bool
     * @throws \LogicException
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        //Defines access rights
        switch ($attribute) {
            case self::ALL:
                return $this->decisionManager->decide($token, array('ROLE_USER'));
                break;
            case self::DISPLAY:
                return $this->isOwner($token, $subject);
                break;
        }

        throw new \LogicException('Invalid attribute: ' . $attribute);
    }

    /**
     * Checks if user is owner or has admin rights
     * @return bool
     */
    private function isOwner($token, Transaction $transaction)
    {
        if ($this->decisionManager->decide($token, array('ROLE_USER'))) {
            return $this->isAdmin($token) ? true : $transaction->getUserId() === $token->getUser()->getId();
        }

        return false;
    }

    /**
     * Checks if user has admin rights
     * @return bool
     */
    private function isAdmin($token)
    {
        return $this->decisionManager->decide($token, array($this->roleNeeded));
    }
}