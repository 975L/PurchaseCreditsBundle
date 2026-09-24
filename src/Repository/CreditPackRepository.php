<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Repository;

use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CreditPack> */
class CreditPackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditPack::class);
    }

    // The packs on sale, in the order the back office sets
    /** @return list<CreditPack> */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.published = true')
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.credits', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
