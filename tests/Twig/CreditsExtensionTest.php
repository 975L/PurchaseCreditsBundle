<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Twig;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use c975L\PurchaseCreditsBundle\Twig\Extension\CreditsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

class CreditsExtensionTest extends TestCase
{
    // An extension whose signed-in user is $user, over a ledger summing to 12
    private function extension(?SymfonyUserInterface $user, ?CreditPackRepository $packRepository = null): CreditsExtension
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $creditService = $this->createStub(CreditServiceInterface::class);
        $creditService->method('getBalance')->willReturn(12);

        return new CreditsExtension($packRepository ?? $this->createStub(CreditPackRepository::class), $creditService, $security);
    }

    public function testTheBalanceIsTheSignedInUsersOne(): void
    {
        $this->assertSame(12, $this->extension($this->createStub(UserInterface::class))->getBalance());
    }

    // null rather than 0: the block tells a visitor to sign in, where a user at zero is offered the packs
    public function testAVisitorHasNoBalance(): void
    {
        $this->assertNull($this->extension(null)->getBalance());
    }

    // A user of another firewall, not an account of the core, has no ledger either
    public function testAUserOutsideTheCoreAccountsHasNoBalance(): void
    {
        $this->assertNull($this->extension($this->createStub(SymfonyUserInterface::class))->getBalance());
    }

    // Only the published packs, as the repository orders them
    public function testThePacksAreThePublishedOnes(): void
    {
        $packs = [new CreditPack()->setCredits(10)];
        $repository = $this->createMock(CreditPackRepository::class);
        $repository->expects($this->once())->method('findPublished')->willReturn($packs);

        $this->assertSame($packs, $this->extension(null, $repository)->getPacks());
    }
}
