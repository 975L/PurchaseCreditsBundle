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
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CreditTransaction> */
class CreditTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditTransaction::class);
    }

    // The balance of a user, summed over the ledger
    public function sumForUser(UserInterface $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.amount), 0)')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    // The movements of a user, latest first
    /** @return list<CreditTransaction> */
    public function findForUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // Whether a movement already answers to this reference, so a purchase delivered twice is credited once
    public function hasReference(UserInterface $user, string $reference): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'reference' => $reference]);
    }
}
