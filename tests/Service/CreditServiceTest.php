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
use c975L\PaymentBundle\Service\PaymentTestModeInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Exception\InsufficientCreditsException;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreditServiceTest extends TestCase
{
    // A service over a ledger whose sum is $balance, the payments running in test mode when $testMode says so
    private function service(int $balance, EntityManagerInterface $entityManager, bool $testMode = false): CreditService
    {
        $repository = $this->createStub(CreditTransactionRepository::class);
        $repository->method('sumForUser')->willReturn($balance);
        $repository->method('sumForUserLocked')->willReturn($balance);

        $paymentTestMode = $this->createStub(PaymentTestModeInterface::class);
        $paymentTestMode->method('isEnabled')->willReturn($testMode);

        return new CreditService($repository, $entityManager, $paymentTestMode);
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

    // The balance is read by a locking read, so a spending inside a transaction the caller opened earlier still sees what another one committed
    public function testSpendReadsTheBalanceWithALockingRead(): void
    {
        $repository = $this->createMock(CreditTransactionRepository::class);
        $repository->expects($this->once())->method('sumForUserLocked')->willReturn(5);
        $repository->expects($this->never())->method('sumForUser');
        $entityManager = $this->transactionalEntityManager();
        $entityManager->expects($this->once())->method('persist');

        new CreditService($repository, $entityManager, $this->createStub(PaymentTestModeInterface::class))->spend($this->createStub(UserInterface::class), 5, 'Shortcut');
    }

    // A spending while the payments run in test mode is a rehearsal line, left out once the site sells for real
    public function testSpendInTestModeWritesARehearsalLine(): void
    {
        $entityManager = $this->transactionalEntityManager();
        $entityManager->expects($this->once())->method('persist');

        $transaction = $this->service(5, $entityManager, true)->spend($this->createStub(UserInterface::class), 5, 'Shortcut');

        $this->assertTrue($transaction->isTestMode());
    }

    // A grant is a real line unless the caller says it is a rehearsal (a basket paid in test mode)
    public function testGrantIsARealLineUnlessToldOtherwise(): void
    {
        $service = $this->service(0, $this->createStub(EntityManagerInterface::class), true);
        $user = $this->createStub(UserInterface::class);

        $this->assertFalse($service->grant($user, 3, 'Gift')->isTestMode());
        $this->assertTrue($service->grant($user, 3, 'Purchase', 'TEST-1-7', true)->isTestMode());
    }

    // A refund takes credits back whatever the balance, the credits spent since staying owed
    public function testTakeBackWritesANegativeLineWhateverTheBalance(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $transaction = $this->service(0, $entityManager)->takeBack($this->createStub(UserInterface::class), 120, 'Refund', '20260924-1-7-refund');

        $this->assertSame(-120, $transaction->getAmount());
        $this->assertSame('20260924-1-7-refund', $transaction->getReference());
    }

    public function testTakeBackRefusesANonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service(0, $this->createStub(EntityManagerInterface::class))->takeBack($this->createStub(UserInterface::class), 0, 'Nothing', 'ref');
    }
}
