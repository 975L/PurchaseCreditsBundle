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
use c975L\PurchaseCreditsBundle\Entity\PurchaseCredits;

class PurchaseCreditsVoter extends Voter
{
    private $decisionManager;
    private $roleNeeded;

    public const DASHBOARD = 'dashboard';
    public const PURCHASE = 'purchase';

    private const ATTRIBUTES = array(
        self::DASHBOARD,
        self::PURCHASE,
    );

    public function __construct(AccessDecisionManagerInterface $decisionManager, string $roleNeeded)
    {
        $this->decisionManager = $decisionManager;
        $this->roleNeeded = $roleNeeded;
    }

    protected function supports($attribute, $subject)
    {
        if (null !== $subject) {
            return $subject instanceof PurchaseCredits && in_array($attribute, self::ATTRIBUTES);
        }

        return in_array($attribute, self::ATTRIBUTES);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        //Defines access rights
        switch ($attribute) {
            case self::DASHBOARD:
            case self::PURCHASE:
                return $this->decisionManager->decide($token, array('ROLE_USER'));
        }

        throw new \LogicException('Invalid attribute: ' . $attribute);
    }
}