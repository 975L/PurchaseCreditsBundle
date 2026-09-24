<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Service;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Exception\InsufficientCreditsException;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreditServiceTest extends TestCase
{
    // A service over a ledger whose sum is $balance, the transaction running its callback straight away
    private function service(int $balance, EntityManagerInterface $entityManager): CreditService
    {
        $repository = $this->createStub(CreditTransactionRepository::class);
        $repository->method('sumForUser')->willReturn($balance);

        return new CreditService($repository, $entityManager);
    }

    // An EntityManager whose transaction runs its callback and, as Doctrine does, closes itself when an exception crosses it
    private function transactionalEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static function (callable $callback) use ($entityManager): mixed {
            try {
                return $callback($entityManager);
            } catch (\Throwable $throwable) {
                $entityManager->close();

                throw $throwable;
            }
        });

        return $entityManager;
    }

    public function testGetBalanceIsTheSumOfTheLedger(): void
    {
        $service = $this->service(36, $this->createStub(EntityManagerInterface::class));

        $this->assertSame(36, $service->getBalance($this->createStub(UserInterface::class)));
    }

    public function testGrantWritesAPositiveLine(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(CreditTransaction::class));
        $entityManager->expects($this->once())->method('flush');

        $transaction = $this->service(0, $entityManager)->grant($this->createStub(UserInterface::class), 3, 'Sign-up', 'signup');

        $this->assertSame(3, $transaction->getAmount());
        $this->assertSame('signup', $transaction->getReference());
    }

    public function testGrantRefusesANonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service(0, $this->createStub(EntityManagerInterface::class))->grant($this->createStub(UserInterface::class), 0, 'Nothing');
    }

    public function testSpendLocksTheUserAndWritesANegativeLine(): void
    {
        $user = $this->createStub(UserInterface::class);
        $entityManager = $this->transactionalEntityManager();
        $entityManager->expects($this->once())->method('lock')->with($user, LockMode::PESSIMISTIC_WRITE);
        $entityManager->expects($this->once())->method('persist');

        $transaction = $this->service(5, $entityManager)->spend($user, 5, 'Shortcut');

        $this->assertSame(-5, $transaction->getAmount());
    }

    public function testSpendBeyondTheBalanceWritesNothing(): void
    {
        $entityManager = $this->transactionalEntityManager();
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('close');

        try {
            $this->service(2, $entityManager)->spend($this->createStub(UserInterface::class), 3, 'Shortcut');
            $this->fail('No exception thrown');
        } catch (InsufficientCreditsException $exception) {
            $this->assertSame(2, $exception->balance);
            $this->assertSame(3, $exception->amount);
        }
    }

    public function testSpendRefusesANonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service(10, $this->createStub(EntityManagerInterface::class))->spend($this->createStub(UserInterface::class), -1, 'Nothing');
    }
}
