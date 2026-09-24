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
    ) {
    }

    // The credits a user has left
    public function getBalance(UserInterface $user): int
    {
        return $this->transactionRepository->sumForUser($user);
    }

    // Adds credits to a user
    public function grant(UserInterface $user, int $amount, string $description, ?string $reference = null): CreditTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credits granted must be positive.');
        }

        $transaction = $this->transaction($user, $amount, $description, $reference);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    // Takes credits from a user, the user's row being locked while the balance is read and the line written, so two spendings at once cannot both pass on the same credits
    public function spend(UserInterface $user, int $amount, string $description, ?string $reference = null): CreditTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credits spent must be positive.');
        }

        $result = $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($user, $amount, $description, $reference): CreditTransaction | int {
            $entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

            $balance = $this->getBalance($user);
            if ($balance < $amount) {
                return $balance;
            }

            $transaction = $this->transaction($user, -$amount, $description, $reference);
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

    // A ledger line, not yet persisted
    private function transaction(UserInterface $user, int $amount, string $description, ?string $reference): CreditTransaction
    {
        return new CreditTransaction()
            ->setUser($user)
            ->setAmount($amount)
            ->setDescription($description)
            ->setReference($reference)
        ;
    }
}
