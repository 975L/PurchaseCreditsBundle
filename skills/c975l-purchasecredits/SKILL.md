---
name: c975l-purchasecredits
description: "Use this skill when working with prepaid credits in a Symfony application built on the c975L ecosystem with c975l/purchasecredits-bundle. Covers the credit packs sold through PaymentBundle's basket, the per-user ledger, and the spending API a site's own services call. Triggers on: credits, prepaid credits, credit pack, buy credits, spend credits, balance, CreditPack, CreditTransaction, CreditServiceInterface, getBalance, grant, spend, InsufficientCreditsException, CreditBasketItemProvider, purchasecredits_packs, purchasecredits_balance, sign-up bonus, ledger."
---

# c975L PurchaseCreditsBundle

> Prepaid credits on the c975L core — credit packs sold through the basket, a per-user ledger and a spending API the site's own services call. Checkout is delegated to `c975l/payment-bundle`.

**Package:** `c975l/purchasecredits-bundle` · **Namespace:** `c975L\PurchaseCreditsBundle\` · **Twig namespace:** `@c975LPurchaseCredits` · **Translation domain:** `purchasecredits`

**Key source paths** (relative to the package root):
`src/Entity/`, `src/Repository/`, `src/Service/`, `src/Exception/`, `src/Controller/Management/`, `src/Management/`, `src/Twig/Extension/`, `src/Form/Block/`, `templates/blocks/`, `templates/management/`, `config/services.yaml`

**Related documentation:** this package's `README.md` is the reference. The ecosystem's rules (configuration, blocks, management contributions) live in `c975l/core-bundle`, the basket in `c975l/payment-bundle`.

**Related skills:** `c975l-payment-items` and `c975l-payment-checkout` in `c975l/payment-bundle`; `c975l-blocks` and `c975l-management` in `c975l/core-bundle`.

## What the site does, what the bundle does

- The bundle sells `CreditPack` rows through the basket (`CreditBasketItemProvider`, kind `purchasecredits`) and credits the account once the basket is paid.
- The site decides what costs credits. Its own services call `CreditServiceInterface`:

```php
use c975L\PurchaseCreditsBundle\Exception\InsufficientCreditsException;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;

public function __construct(private readonly CreditServiceInterface $creditService) {}

$balance = $this->creditService->getBalance($user);

try {
    $this->creditService->spend($user, 5, 'Short link /abc', (string) $shortcut->getId());
} catch (InsufficientCreditsException $exception) {
    // $exception->balance, $exception->amount - nothing was written
}

// A sign-up bonus, from the site's own registration code
$this->creditService->grant($user, 3, 'Welcome credits', 'signup');
```

## Rules an agent must keep

- The ledger is the only truth: a balance is the sum of `CreditTransaction` lines. Never add a `credits` column on the user.
- A line is never edited nor deleted; a mistake is corrected by the reversing line (the back office only offers "new").
- `spend()` locks the user's row while it reads the balance and writes: never read `getBalance()` then write a line yourself.
- `InsufficientCreditsException` leaves the EntityManager open: catch it and carry on, no reset needed.
- A pack is only sold to a signed-in user, credits landing on an account.

## Front

Place the `purchasecredits_packs` block on any page from the back office. Templates may also call `purchasecredits_packs()` and `purchasecredits_balance()` (null for a visitor).
