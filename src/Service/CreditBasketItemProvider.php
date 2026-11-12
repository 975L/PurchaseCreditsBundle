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
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Contract\AccountBasketItemProviderInterface;
use c975L\PaymentBundle\Contract\BasketItemProviderInterface;
use c975L\PaymentBundle\Contract\RefundableBasketItemProviderInterface;
use c975L\PaymentBundle\Entity\Basket;
use c975L\PaymentBundle\Enum\BasketStatus;
use c975L\PaymentBundle\Service\VatCalculator;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

// Plugs the credit packs into PaymentBundle's basket and checkout (see BasketItemProviderInterface). Credits land on an account: a visitor fills the basket freely and is asked to sign in at the checkout (see AccountBasketItemProviderInterface), and a refund takes them back (see RefundableBasketItemProviderInterface)
class CreditBasketItemProvider implements BasketItemProviderInterface, AccountBasketItemProviderInterface, RefundableBasketItemProviderInterface
{
    public const string KIND = 'purchasecredits';

    public function __construct(
        private readonly CreditPackRepository $packRepository,
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly CreditServiceInterface $creditService,
        private readonly ConfigServiceInterface $configService,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getKind(): string
    {
        return self::KIND;
    }

    public function findItem(int | string $id): ?object
    {
        $pack = $this->packRepository->find((int) $id);

        // A pack saved at no price before prices had to be positive is not sold: it would hand out credits without limit
        return null !== $pack && $pack->isPublished() && $pack->getPrice() > 0 ? $pack : null;
    }

    public function validateAddition(object $item, int $quantity): ?string
    {
        // A removal is never refused
        if ($quantity <= 0) {
            return null;
        }

        if (!$item instanceof CreditPack || !$item->isPublished()) {
            return $this->translator->trans('label.pack_unavailable', [], 'purchasecredits');
        }

        return null;
    }

    // Asked again at the checkout: a pack may have been withdrawn since, and the credits need an account to land on - the one signed in now when the basket was filled before signing in
    public function validateCheckout(Basket $basket, array $itemsOfThisKind): ?string
    {
        foreach (array_keys($itemsOfThisKind) as $id) {
            if (null === $this->findItem($id)) {
                return $this->translator->trans('label.pack_unavailable', [], 'purchasecredits');
            }
        }

        if (null !== $basket->getUser()) {
            return null;
        }

        // An order already placed whose account is gone (a shared order paid by someone else) is never handed to whoever is signed in
        if (BasketStatus::New !== $basket->getStatus()) {
            return $this->translator->trans('label.account_not_found', [], 'purchasecredits');
        }

        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            return $this->translator->trans('label.sign_in_required', [], 'purchasecredits');
        }

        $basket->setUser($user);

        return null;
    }

    public function toBasketData(object $item, int $quantity): array
    {
        /** @var CreditPack $item */
        $total = $quantity * (int) $item->getPrice();
        $title = $this->translator->trans('label.pack_title', ['%count%' => $item->getCredits()], 'purchasecredits');

        return [
            'item' => [
                'id' => $item->getId(),
                'title' => $title,
                // Frozen here: what the customer agreed to is what is credited, whatever the pack becomes before the payment
                'credits' => $item->getCredits(),
                'price' => $item->getPrice(),
                'currency' => (string) ($this->configService->get('shop-currency') ?: 'EUR'),
                'vat' => $item->getVat(),
                'limitedQuantity' => 0,
                'orderedQuantity' => 0,
            ],
            // No catalogue page of its own: the packs are a block placed wherever the site wants them
            'parent' => [
                'title' => $title,
            ],
            'type' => self::KIND,
            'quantity' => $quantity,
            'totalVat' => VatCalculator::included($total, $item->getVat()),
            'total' => $total,
        ];
    }

    // Credits are neither shipped nor downloaded: a service rendered on the site
    public function getContentFlags(array $itemData): int
    {
        return Basket::CONTENT_FLAG_SERVICE;
    }

    public function onBasketValidated(Basket $basket, array $itemsOfThisKind, array $requestData): array
    {
        return [];
    }

    // Credits the account, one ledger line per pack, with the number of credits frozen in the basket. A basket paid in test mode writes rehearsal lines, left out of the balance once the site sells for real
    public function onBasketPaid(Basket $basket, array $itemsOfThisKind, array $checkoutData): void
    {
        $user = $basket->getUser();
        if (null === $user) {
            return;
        }

        foreach ($itemsOfThisKind as $id => $itemContent) {
            $credits = $this->credits($itemContent);
            $reference = $this->reference($basket, $id);

            if ($credits <= 0 || $this->transactionRepository->hasReference($user, $reference)) {
                continue;
            }

            $description = $this->translator->trans('label.transaction_purchase', ['%count%' => $credits], 'purchasecredits', $basket->getLocale());
            $this->creditService->grant($user, $credits, $description, $reference, $basket->isTestMode());
        }
    }

    // A full refund or a lost dispute takes back what the payment credited, one reversing line per pack, even below zero: the credits spent since stay owed
    public function onBasketRefunded(Basket $basket, array $itemsOfThisKind): void
    {
        $user = $basket->getUser();
        if (null === $user) {
            return;
        }

        foreach ($itemsOfThisKind as $id => $itemContent) {
            $credits = $this->credits($itemContent);
            $reference = $this->reference($basket, $id);

            // Only what was credited, and only once
            if ($credits <= 0 || !$this->transactionRepository->hasReference($user, $reference) || $this->transactionRepository->hasReference($user, $reference . '-refund')) {
                continue;
            }

            $description = $this->translator->trans('label.transaction_refund', ['%count%' => $credits], 'purchasecredits', $basket->getLocale());
            $this->creditService->takeBack($user, $credits, $description, $reference . '-refund', $basket->isTestMode());
        }
    }

    // The credits a basket line is worth, as frozen in the basket
    private function credits(array $itemContent): int
    {
        return (int) ($itemContent['item']['credits'] ?? 0) * (int) ($itemContent['quantity'] ?? 0);
    }

    // What the ledger line of a basket line answers to
    private function reference(Basket $basket, int | string $id): string
    {
        return $basket->getNumber() . '-' . $id;
    }
}
