# UPGRADE

## v4.x > v5.x

A rewrite on the c975L core (Symfony 8, `c975l/core-bundle`, `c975l/payment-bundle` v6). Nothing of v4's API survives: no routes, no `TransactionServiceInterface`, no `credits` column on the user.

| v4 | v5 |
| --- | --- |
| `$user->getCredits()` | `CreditServiceInterface::getBalance($user)` |
| `TransactionServiceInterface` + `setCredits(-$cost)` + `$user->setCredits()` | `CreditServiceInterface::spend($user, $cost, $description, $reference)` |
| Credits added by hand on the user | `CreditServiceInterface::grant()` |
| `/purchase-credits` pages | the `purchasecredits_packs` block, placed on a page |
| `user_transactions` table | `credit_transaction` (ledger, the balance is its sum) |
| Prices in the bundle's config | `CreditPack` rows, in the back office |

### Taking over a v4 ledger

The v4 balance stored on the user is what the site actually used; its `user_transactions` does not always add up to it (balances were sometimes corrected by hand). Import the lines of the users that still exist, then one adjustment line per user so the new balance equals the old one. Run on the new database once the users are imported with their v4 ids, `old` being the v4 database:

```sql
INSERT INTO credit_transaction (user_id, amount, description, reference, created_at)
SELECT t.user_id, t.credits, LEFT(COALESCE(t.description, ''), 255), t.order_id, COALESCE(t.creation, NOW())
FROM old.user_transactions t
JOIN user u ON u.id = t.user_id
WHERE t.credits IS NOT NULL AND t.credits <> 0;

INSERT INTO credit_transaction (user_id, amount, description, reference, created_at)
SELECT o.id, o.credits - COALESCE(SUM(t.credits), 0), 'Balance taken over from v4', 'v4-balance', NOW()
FROM old.user o
JOIN user u ON u.id = o.id
LEFT JOIN old.user_transactions t ON t.user_id = o.id
GROUP BY o.id, o.credits
HAVING o.credits - COALESCE(SUM(t.credits), 0) <> 0;
```

Then drop the `credits` column from `App\Entity\User`.

### The default branch is `main`

`master` was renamed `main` on 24/09/2026. A `composer.json` requiring `dev-master` now requires `dev-main`; GitHub redirects the old branch name for clones and links.

## v3.x > v4.x

Changed `localizeddate` to `format_datetime`

## v2.x > v3.x

`c975LEmailBundle` now use `Symfony\Component\Mailer\MailerInterface`and `Symfony\Component\Mime\Email` which are NOT compatible with Symfony 3.x.

## v1.x > v2.x

When upgrading from v1.x to v2.x you should(must) do the following if they apply to your case:

- The parameters entered in `config.yml` are not used anymore as they are managed by c975L/ConfigBundle, so you can delete them.
- As the parameters are not in `config.yml`, we can't access them via `$this[->container]->getParameter()`, so you have to replace `$this->getParameter('c975_l_purchase_credits.XXX')` by `$configService->getParameter('c975LPurchaseCredits.XXX')`, where `$configService` is the injection of `c975L\ConfigBundle\Service\ConfigServiceInterface`.
- Before the first use of parameters, you **MUST** use the console command `php bin/console config:create` to create the config files with default data.
