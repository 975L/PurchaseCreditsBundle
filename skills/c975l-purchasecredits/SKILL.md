---
name: c975l-purchasecredits
description: "Use this skill when working with prepaid credits in a Symfony application built on the c975L ecosystem with c975l/purchasecredits-bundle. Covers the credit packs sold through PaymentBundle's basket, the per-user ledger, and the spending API a site's own services call. Triggers on: credits, prepaid credits, credit pack, buy credits, spend credits, balance, CreditPack, CreditTransaction, CreditServiceInterface, getBalance, grant, spend, InsufficientCreditsException, CreditBasketItemProvider, AccountBasketItemProviderInterface, sign in at checkout, purchasecredits_packs, purchasecredits_balance, packs_intro, pack_class, pack_top, pack_extra, featured pack, StylesheetProvider, sign-up bonus, ledger, purchasecredits-test-mode, test mode, PurchaseCreditsShortcutProvider, PurchaseCreditsDemoFixtureProvider, demo fixtures."
---

# c975L PurchaseCreditsBundle

> Prepaid credits on the c975L core — credit packs sold through the basket, a per-user ledger and a spending API the site's own services call. Checkout is delegated to `c975l/payment-bundle`.

**Package:** `c975l/purchasecredits-bundle` · **Namespace:** `c975L\PurchaseCreditsBundle\` · **Twig namespace:** `@c975LPurchaseCredits` · **Translation domain:** `purchasecredits`

**Key source paths** (relative to the package root):
`src/Entity/`, `src/Repository/`, `src/Service/`, `src/Exception/`, `src/Controller/Management/`, `src/Management/`, `src/Twig/Extension/`, `src/Form/Block/`, `templates/blocks/`, `templates/management/`, `sass/`, `config/services.yaml`, `config/configs.json`

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
- Credits land on an account: a visitor may fill the basket, PaymentBundle asks them to sign in at the checkout (`CreditBasketItemProvider` implements `AccountBasketItemProviderInterface`). Never hide the "Buy" button from a visitor.
- The price currency is PaymentBundle's `shop-currency`, falling back on `EUR` while it is not set.
- The demo dataset (`PurchaseCreditsDemoFixtureProvider`) seeds three published packs and no ledger line: a site wanting a ledger to show writes it for the accounts it seeds itself.

## Test mode

`purchasecredits-test-mode` (bool, loaded by `c975l:config:load-all`) shows a warning banner above the packs: nothing is really sold. Admins flip it from a dashboard toggle tile (`PurchaseCreditsShortcutProvider`, route `management_purchasecredits_test_mode_toggle`). It is independent of PaymentBundle's own test mode, which is what actually keeps the charge fake.

## Front

Place the `purchasecredits_packs` block on any page from the back office. Templates may also call `purchasecredits_packs()` and `purchasecredits_balance()` (null for a visitor).

The block's styles ship in `sass/styles.scss`, added to every page by `StylesheetProvider`. A site extends `@c975LPurchaseCredits/blocks/Packs.html.twig` and fills its empty `packs_intro`, `pack_class`, `pack_top` and `pack_extra` blocks to say what a pack buys; each pack is UiBundle's pricing card (`card--pricing`), and `card--featured` (in `pack_class`) and a `card-ribbon` badge (in `pack_top`) put a pack forward. The pack's price takes PaymentBundle's `price price--standalone` classes, grown inside the pricing card by its `--price-size` token, so it is restyled there, not here.
