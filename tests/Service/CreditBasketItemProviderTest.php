<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Service;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PaymentBundle\Entity\Basket;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use c975L\PurchaseCreditsBundle\Repository\CreditPackRepository;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditBasketItemProvider;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreditBasketItemProviderTest extends TestCase
{
    // A provider whose signed-in user is $user, the translator answering the key it is asked for
    private function provider(?UserInterface $user, ?CreditServiceInterface $creditService = null, bool $alreadyCredited = false): CreditBasketItemProvider
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $transactionRepository = $this->createStub(CreditTransactionRepository::class);
        $transactionRepository->method('hasReference')->willReturn($alreadyCredited);

        return new CreditBasketItemProvider(
            $this->createStub(CreditPackRepository::class),
            $transactionRepository,
            $creditService ?? $this->createStub(CreditServiceInterface::class),
            $this->createStub(ConfigServiceInterface::class),
            $security,
            $translator,
        );
    }

    private function pack(): CreditPack
    {
        return new CreditPack()->setCredits(10)->setPrice(500)->setVat(0.0);
    }

    // A basket as it stands once paid
    private function paidBasket(?UserInterface $user): Basket
    {
        return new Basket()->setUser($user)->setNumber('20260924-1')->setLocale('fr');
    }

    public function testAVisitorCannotAddAPack(): void
    {
        $this->assertSame('label.sign_in_required', $this->provider(null)->validateAddition($this->pack(), 1));
    }

    public function testASignedInUserCanAddAPack(): void
    {
        $this->assertNull($this->provider($this->createStub(UserInterface::class))->validateAddition($this->pack(), 1));
    }

    public function testAWithdrawnPackCannotBeAdded(): void
    {
        $pack = $this->pack()->setPublished(false);

        $this->assertSame('label.pack_unavailable', $this->provider($this->createStub(UserInterface::class))->validateAddition($pack, 1));
    }

    public function testARemovalIsNeverRefused(): void
    {
        $this->assertNull($this->provider(null)->validateAddition($this->pack(), -1));
    }

    public function testBasketDataFreezesTheCreditsAndTheTotal(): void
    {
        $data = $this->provider(null)->toBasketData($this->pack(), 2);

        $this->assertSame(10, $data['item']['credits']);
        $this->assertSame(1000, $data['total']);
        $this->assertSame(CreditBasketItemProvider::KIND, $data['type']);
        $this->assertSame(Basket::CONTENT_FLAG_SERVICE, $this->provider(null)->getContentFlags($data));
    }

    // A shop whose currency is not set yet - a demo, a site just installed - sells in euros, as the back office already assumes, rather than an empty currency the checkout cannot charge in
    public function testBasketDataFallsBackOnEurosWhenTheShopCurrencyIsNotSet(): void
    {
        $data = $this->provider(null)->toBasketData($this->pack(), 1);

        $this->assertSame('EUR', $data['item']['currency']);
    }

    public function testCheckoutAttachesTheSignedInUserToAnAnonymousBasket(): void
    {
        $user = $this->createStub(UserInterface::class);
        $basket = new Basket();

        $this->assertNull($this->provider($user)->validateCheckout($basket, []));
        $this->assertSame($user, $basket->getUser());
    }

    public function testCheckoutRefusesABasketWithNoAccount(): void
    {
        $this->assertSame('label.sign_in_required', $this->provider(null)->validateCheckout(new Basket(), []));
    }

    public function testPaymentGrantsTheFrozenCreditsTimesTheQuantity(): void
    {
        $user = $this->createStub(UserInterface::class);
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->once())->method('grant')->with($user, 20, 'label.transaction_purchase', '20260924-1-7');

        $this->provider($user, $creditService)->onBasketPaid($this->paidBasket($user), [7 => ['item' => ['credits' => 10], 'quantity' => 2]], []);
    }

    // A pack withdrawn from sale is out of reach of a basket, even one that already holds it
    public function testAWithdrawnPackIsNotFound(): void
    {
        $packRepository = $this->createStub(CreditPackRepository::class);
        $packRepository->method('find')->willReturn($this->pack()->setPublished(false));

        $provider = new CreditBasketItemProvider(
            $packRepository,
            $this->createStub(CreditTransactionRepository::class),
            $this->createStub(CreditServiceInterface::class),
            $this->createStub(ConfigServiceInterface::class),
            $this->createStub(Security::class),
            $this->createStub(TranslatorInterface::class),
        );

        $this->assertNull($provider->findItem(7));
    }

    // Checked again at the checkout, the basket having lived in the session since the pack was withdrawn
    public function testCheckoutRefusesAPackWithdrawnSince(): void
    {
        $this->assertSame('label.pack_unavailable', $this->provider($this->createStub(UserInterface::class))->validateCheckout(new Basket(), [7 => []]));
    }

    // A basket already bound to an account keeps it, whoever is signed in at the checkout
    public function testCheckoutKeepsTheAccountTheBasketAlreadyHas(): void
    {
        $owner = $this->createStub(UserInterface::class);
        $basket = new Basket()->setUser($owner);

        $this->assertNull($this->provider($this->createStub(UserInterface::class))->validateCheckout($basket, []));
        $this->assertSame($owner, $basket->getUser());
    }

    // Credits need an account to land on: a paid basket without one grants nothing rather than failing the payment's delivery
    public function testAPaidBasketWithoutAccountGrantsNothing(): void
    {
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->never())->method('grant');

        $this->provider(null, $creditService)->onBasketPaid($this->paidBasket(null), [7 => ['item' => ['credits' => 10], 'quantity' => 1]], []);
    }

    public function testAPaymentDeliveredTwiceIsCreditedOnce(): void
    {
        $user = $this->createStub(UserInterface::class);
        $creditService = $this->createMock(CreditServiceInterface::class);
        $creditService->expects($this->never())->method('grant');

        $this->provider($user, $creditService, true)->onBasketPaid($this->paidBasket($user), [7 => ['item' => ['credits' => 10], 'quantity' => 2]], []);
    }
}
