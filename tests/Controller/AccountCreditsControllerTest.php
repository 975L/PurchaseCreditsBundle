<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Controller;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\ConfigBundle\Service\LocalizedRouteNegotiator;
use c975L\ConfigBundle\Service\SiteLocales;
use c975L\PurchaseCreditsBundle\Controller\AccountCreditsController;
use c975L\PurchaseCreditsBundle\Repository\CreditTransactionRepository;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

class AccountCreditsControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $rendered = [];

    // The member's own history, a visitor being sent to the login form by the firewall
    public function testRequiresAMember(): void
    {
        $attributes = new \ReflectionClass(AccountCreditsController::class)->getAttributes(IsGranted::class);

        $this->assertSame('ROLE_USER', $attributes[0]->newInstance()->attribute);
    }

    // The page asked for, read a page's worth further on
    public function testReadsThePageAskedFor(): void
    {
        $offsets = [];
        $this->controller(45, $offsets)->index($this->request(2));

        $this->assertSame(2, $this->rendered['currentPage']);
        $this->assertSame(3, $this->rendered['pageCount']);
        $this->assertSame([AccountCreditsController::PER_PAGE], $offsets);
    }

    // A page past the last one, or below the first, answers the nearest there is
    public function testClampsThePageToTheHistory(): void
    {
        $offsets = [];
        $this->controller(5, $offsets)->index($this->request(9));
        $this->assertSame(1, $this->rendered['currentPage']);

        $this->controller(5, $offsets)->index($this->request(-3));
        $this->assertSame(1, $this->rendered['currentPage']);
    }

    // The history read in a language named in its url, which the negotiator never moves
    private function request(int $page): Request
    {
        $request = Request::create('/en/account/credits', 'GET', ['page' => $page]);
        $request->attributes->set('_locale', 'en');

        return $request;
    }

    // Over a ledger of $count movements, the offsets it was read at collected
    /** @param list<int> $offsets */
    private function controller(int $count, array &$offsets): AccountCreditsController
    {
        $repository = $this->createStub(CreditTransactionRepository::class);
        $repository->method('countForUser')->willReturn($count);
        $repository->method('findForUser')->willReturnCallback(static function (UserInterface $user, ?int $limit, int $offset) use (&$offsets): array {
            $offsets[] = $offset;

            return [];
        });

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturnCallback(function (string $template, array $context): string {
            $this->rendered = $context;

            return 'page';
        });

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($this->createStub(UserInterface::class), 'main'));

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $tokenStorage);

        $siteLocales = new SiteLocales(['fr', 'en'], 'fr');
        $controller = new AccountCreditsController(
            $this->createStub(CreditServiceInterface::class),
            $repository,
            new LocalizedRouteNegotiator($siteLocales, new LocaleSwitcher('fr', []), $this->createStub(UrlGeneratorInterface::class)),
            $siteLocales,
        );
        $controller->setContainer($container);

        return $controller;
    }
}
