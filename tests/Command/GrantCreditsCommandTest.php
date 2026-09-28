<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Command;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Command\GrantCreditsCommand;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GrantCreditsCommandTest extends TestCase
{
    // A command over an EntityManager finding $user, whatever the email asked for
    private function tester(?UserInterface $user, CreditServiceInterface $creditService): CommandTester
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new CommandTester(new GrantCreditsCommand($entityManager, $creditService));
    }

    public function testGrantsTheCreditsToTheAccount(): void
    {
        $user = $this->createStub(UserInterface::class);
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->once())->method('grant')->with($user, 20, 'Tutorial');
        $creditService->method('getBalance')->willReturn(25);

        $tester = $this->tester($user, $creditService);

        $this->assertSame(Command::SUCCESS, $tester->execute(['email' => 'a@example.com', 'credits' => '20', '--description' => 'Tutorial']));
        $this->assertStringContainsString('balance 25', $tester->getDisplay());
    }

    public function testRefusesAnUnknownAccount(): void
    {
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->never())->method('grant');

        $this->assertSame(Command::FAILURE, $this->tester(null, $creditService)->execute(['email' => 'a@example.com', 'credits' => '20']));
    }

    public function testRefusesCreditsThatAreNotAPositiveWholeNumber(): void
    {
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->never())->method('grant');

        foreach (['0', '-3', '2.5', 'ten'] as $credits) {
            $this->assertSame(Command::FAILURE, $this->tester($this->createStub(UserInterface::class), $creditService)->execute(['email' => 'a@example.com', 'credits' => $credits]));
        }
    }
}
