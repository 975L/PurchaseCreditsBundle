<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Entity;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

// One movement of a user's credits, positive when granted or bought, negative when spent. The ledger is the only truth: a balance is the sum of its lines, never a counter stored beside them
#[ORM\Entity(repositoryClass: CreditTransactionRepository::class)]
#[ORM\Table(name: 'credit_transaction')]
#[ORM\Index(name: 'idx_credit_transaction_user', columns: ['user_id'])]
class CreditTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Deleted with the account: a ledger line names a person, and nothing is owed on credits once the account is gone
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserInterface $user = null;

    #[ORM\Column]
    private int $amount = 0;

    #[ORM\Column(length: 255)]
    private string $description = '';

    // What the movement answers to - the basket number of a purchase, the site's own id of what was paid for with credits
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Only for the import of a former ledger, whose lines keep the date they were written on
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
