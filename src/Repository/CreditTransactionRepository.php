<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Repository;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PaymentBundle\Service\PaymentTestModeInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CreditTransaction> */
class CreditTransactionRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PaymentTestModeInterface $testMode,
    ) {
        parent::__construct($registry, CreditTransaction::class);
    }

    // The balance of a user, summed over the ledger
    public function sumForUser(UserInterface $user): int
    {
        return (int) $this->sumQuery($user)->getSingleScalarResult();
    }

    // The same sum, read as a locking read of the lines (SELECT ... FOR UPDATE), so it sees what a concurrent spending committed even inside a transaction the caller opened earlier - to be run inside a transaction
    public function sumForUserLocked(UserInterface $user): int
    {
        return (int) $this->sumQuery($user)
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getSingleScalarResult()
        ;
    }

    // The movements of a user, latest first, a slice of them when a limit is given
    /** @return list<CreditTransaction> */
    public function findForUser(UserInterface $user, ?int $limit = null, int $offset = 0): array
    {
        return $this->forUser($user)
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    // How many movements a user has, for the history's pages
    public function countForUser(UserInterface $user): int
    {
        return (int) $this->forUser($user)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    // Whether a movement already answers to this reference, so a purchase delivered twice is credited once
    public function hasReference(UserInterface $user, string $reference): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'reference' => $reference]);
    }

    // The latest movements of every account, the rehearsal lines filtered as for a balance
    /** @return list<CreditTransaction> */
    public function findLatest(int $limit): array
    {
        return $this->lines()
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    // The credits granted, spent and still held over every account, with the number of lines
    /** @return array{granted: int, spent: int, balance: int, lines: int} */
    public function totals(): array
    {
        $row = $this->lines()
            ->select('COALESCE(SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END), 0) AS granted')
            ->addSelect('COALESCE(SUM(CASE WHEN t.amount < 0 THEN 0 - t.amount ELSE 0 END), 0) AS spent')
            ->addSelect('COUNT(t.id) AS lineCount')
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'granted' => (int) $row['granted'],
            'spent' => (int) $row['spent'],
            'balance' => (int) $row['granted'] - (int) $row['spent'],
            'lines' => (int) $row['lineCount'],
        ];
    }

    // The sum of a user's lines, as a query
    private function sumQuery(UserInterface $user): Query
    {
        return $this->forUser($user)
            ->select('COALESCE(SUM(t.amount), 0)')
            ->getQuery()
        ;
    }

    // The lines of a user
    private function forUser(UserInterface $user): QueryBuilder
    {
        return $this->lines()
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
        ;
    }

    // The lines, the rehearsal ones counted only while the payments run in test mode: the one filter the balance, the history, the export and the agents' reads share, so they never disagree
    private function lines(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('t');

        if (!$this->testMode->isEnabled()) {
            $queryBuilder->andWhere('t.testMode = false');
        }

        return $queryBuilder;
    }
}
