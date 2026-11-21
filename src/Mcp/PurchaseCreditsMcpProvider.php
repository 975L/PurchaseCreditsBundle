<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Mcp;

use c975L\ConfigBundle\Mcp\McpTool;
use c975L\ConfigBundle\Mcp\McpToolProviderInterface;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use Mcp\Server\Exception\ToolCallException;
use Symfony\Bundle\SecurityBundle\Security;

// The credits on the site's MCP endpoint, for an account managing them on the back office: the packs, the latest ledger lines and the totals read, nothing granted, taken nor priced by an agent
class PurchaseCreditsMcpProvider implements McpToolProviderInterface
{
    // Ledger lines a list gives at most
    public const int LIMIT = 50;

    public function __construct(
        private readonly CreditPackRepository $packRepository,
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly ConfigServiceInterface $configService,
        private readonly Security $security,
    ) {
    }

    // Key of the credits for the agent tokens
    public function getMcpKey(): string
    {
        return 'credits';
    }

    // How the credits' tools go together
    public function getMcpInstructions(): string
    {
        return 'Credits are prepaid: packs are sold at a price in cents, and each account holds the sum of its ledger lines, positive when bought or granted, negative when spent or taken back. These tools only read: credits are granted, taken back and priced by a human, from the back office.';
    }

    // The tools of the credits, none of them writing
    public function getMcpTools(): iterable
    {
        yield new McpTool('credits_packs', 'Credit packs', 'Every credit pack, on sale or not, in the order the back office sets: credits, price in cents with its currency, VAT rate, whether it is on sale.', $this->packs(...));
        yield new McpTool('credits_transactions', 'Ledger lines', 'The latest ledger lines of every account, the latest first: date, signed amount, description, reference; the account is not named.', $this->transactions(...));
        yield new McpTool('credits_totals', 'Credit totals', 'The credits granted, spent and still held over every account, with the number of ledger lines.', $this->totals(...));
    }

    // Every pack, in the order the back office sets, its price in cents of the one currency charged
    /** @return list<array{id: ?int, credits: ?int, price: ?int, currency: string, vat: float, published: bool}> */
    public function packs(): array
    {
        $this->guard();
        $currency = (string) ($this->configService->get('shop-currency') ?: 'EUR');

        return array_map(static fn (CreditPack $pack): array => [
            'id' => $pack->getId(),
            'credits' => $pack->getCredits(),
            'price' => $pack->getPrice(),
            'currency' => $currency,
            'vat' => $pack->getVat(),
            'published' => $pack->isPublished(),
        ], $this->packRepository->findBy([], ['position' => 'ASC', 'credits' => 'ASC']));
    }

    // The latest lines of the ledger, without the account they belong to
    /**
     * @param ?int $limit number of lines, 50 at most
     *
     * @return list<array{id: ?int, date: string, amount: int, kind: string, description: string, reference: ?string, testMode: bool}>
     */
    public function transactions(?int $limit = null): array
    {
        $this->guard();

        return array_map(static fn (CreditTransaction $transaction): array => [
            'id' => $transaction->getId(),
            'date' => $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'amount' => $transaction->getAmount(),
            'kind' => 0 > $transaction->getAmount() ? 'debit' : 'credit',
            'description' => $transaction->getDescription(),
            'reference' => $transaction->getReference(),
            'testMode' => $transaction->isTestMode(),
        ], $this->transactionRepository->findLatest(max(1, min(self::LIMIT, $limit ?? self::LIMIT))));
    }

    // The ledger summed over every account
    /** @return array{granted: int, spent: int, balance: int, lines: int} */
    public function totals(): array
    {
        $this->guard();

        return $this->transactionRepository->totals();
    }

    // The credits are the administrator's business, as on the back office
    private function guard(): void
    {
        if (!$this->security->isGranted((string) $this->configService->get('site-role-admin'))) {
            throw new ToolCallException('This account may not read the credits.');
        }
    }
}
