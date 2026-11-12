<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Service;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PaymentBundle\Service\PaymentTestModeInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Exception\InsufficientCreditsException;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

// Reads, grants and spends credits against the ledger (see CreditServiceInterface)
class CreditService implements CreditServiceInterface
{
    public function __construct(
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaymentTestModeInterface $testMode,
    ) {
    }

    // The credits a user has left
    public function getBalance(UserInterface $user): int
    {
        return $this->transactionRepository->sumForUser($user);
    }

    // Adds credits to a user
    public function grant(UserInterface $user, int $amount, string $description, ?string $reference = null, bool $testMode = false): CreditTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credits granted must be positive.');
        }

        return $this->write($this->transaction($user, $amount, $description, $reference, $testMode));
    }

    // Takes back credits handed over for something since undone, the balance allowed below zero
    public function takeBack(UserInterface $user, int $amount, string $description, string $reference, bool $testMode = false): CreditTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credits taken back must be positive.');
        }

        return $this->write($this->transaction($user, -$amount, $description, $reference, $testMode));
    }

    // Takes credits from a user, the user's row being locked and the balance read by a locking read while the line is written, so two spendings at once cannot both pass on the same credits, even inside a transaction the caller opened earlier
    public function spend(UserInterface $user, int $amount, string $description, ?string $reference = null): CreditTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credits spent must be positive.');
        }

        $result = $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($user, $amount, $description, $reference): CreditTransaction | int {
            $entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

            $balance = $this->transactionRepository->sumForUserLocked($user);
            if ($balance < $amount) {
                return $balance;
            }

            $transaction = $this->transaction($user, -$amount, $description, $reference, $this->testMode->isEnabled());
            $entityManager->persist($transaction);
            $entityManager->flush();

            return $transaction;
        });

        // Thrown outside the transaction, as an exception crossing wrapInTransaction() closes the EntityManager
        if (\is_int($result)) {
            throw new InsufficientCreditsException($result, $amount);
        }

        return $result;
    }

    // Persists and flushes a ledger line
    private function write(CreditTransaction $transaction): CreditTransaction
    {
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    // A ledger line, not yet persisted
    private function transaction(UserInterface $user, int $amount, string $description, ?string $reference, bool $testMode): CreditTransaction
    {
        return new CreditTransaction()
            ->setUser($user)
            ->setAmount($amount)
            ->setDescription($description)
            ->setReference($reference)
            ->setTestMode($testMode)
        ;
    }
}
