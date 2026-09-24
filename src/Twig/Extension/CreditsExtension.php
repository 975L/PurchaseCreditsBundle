<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Twig\Extension;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFunction;

// What the templates read live: the packs on sale and the balance of whoever is reading
class CreditsExtension
{
    public function __construct(
        private readonly CreditPackRepository $packRepository,
        private readonly CreditServiceInterface $creditService,
        private readonly Security $security,
    ) {
    }

    // The packs on sale
    /** @return list<CreditPack> */
    #[AsTwigFunction('purchasecredits_packs')]
    public function getPacks(): array
    {
        return $this->packRepository->findPublished();
    }

    // The balance of the signed-in user, null for a visitor
    #[AsTwigFunction('purchasecredits_balance')]
    public function getBalance(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $this->creditService->getBalance($user) : null;
    }
}
