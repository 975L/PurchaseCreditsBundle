<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Entity;

use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// A number of credits sold at a price - what the basket holds when credits are bought. Named by its number of credits, which is why it carries no free-text label to translate
#[ORM\Entity(repositoryClass: CreditPackRepository::class)]
#[ORM\Table(name: 'credit_pack')]
class CreditPack implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $credits = null;

    // In cents, as every price of the ecosystem
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?int $price = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private float $vat = 20.0;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $published = true;

    public function __toString(): string
    {
        return (string) $this->credits;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): self
    {
        $this->credits = $credits;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }
}
