<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditPackCrudController;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditTransactionCrudController;
use c975L\PurchaseCreditsBundle\Management\PurchaseCreditsGuidedProjectProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;

class PurchaseCreditsGuidedProjectProviderTest extends TestCase
{
    // A provider whose url generator records the controllers it is asked for
    private function createProvider(array &$controllers = []): PurchaseCreditsGuidedProjectProvider
    {
        $generator = $this->createStub(AdminUrlGeneratorInterface::class);
        $generator->method('unsetAll')->willReturnSelf();
        $generator->method('setController')->willReturnCallback(function (string $controller) use ($generator, &$controllers) {
            $controllers[] = $controller;

            return $generator;
        });
        $generator->method('setAction')->willReturnSelf();
        $generator->method('generateUrl')->willReturn('/management/purchasecredits');

        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('get')->willReturn('ROLE_ADMIN');

        return new PurchaseCreditsGuidedProjectProvider($generator, $configService);
    }

    // The 10000 block GuidedProjectProviderInterface reserves this bundle, at the step of 10 it states
    public function testGetGuidedProjectsRunsTheBundleOwnBlock(): void
    {
        $projects = $this->createProvider()->getGuidedProjects();

        $this->assertSame(['purchasecredits-pack', 'purchasecredits-gift'], array_column($projects, 'slug'));
        $this->assertSame([10010, 10020], array_column($projects, 'order'));
    }

    // Both screens gate their own index by the site's admin role, so a parcours walking them is dropped for anybody else
    public function testEveryProjectCarriesTheDomainAndTheAdminRole(): void
    {
        foreach ($this->createProvider()->getGuidedProjects() as $project) {
            $this->assertSame('purchasecredits', $project['translation_domain']);
            $this->assertSame('ROLE_ADMIN', $project['role']);
        }
    }

    // Only the opening step leaves the screen, everything after it walking the one the user has been sent to
    public function testOnlyTheFirstStepOfEachProjectCarriesAnUrl(): void
    {
        foreach ($this->createProvider()->getGuidedProjects() as $project) {
            $steps = $project['steps'];

            $this->assertArrayHasKey('url', $steps[0], sprintf('Project "%s" does not open on a screen', $project['slug']));
            $this->assertArrayNotHasKey('highlight', $steps[0]);
            foreach (\array_slice($steps, 1) as $index => $step) {
                $this->assertArrayNotHasKey('url', $step, sprintf('Step %d of "%s" leaves the screen again', $index + 1, $project['slug']));
            }
        }
    }

    // Each parcours opens on the listing its task starts from
    public function testEachProjectOpensOnItsOwnListing(): void
    {
        $controllers = [];
        $this->createProvider($controllers)->getGuidedProjects();

        $this->assertSame([CreditPackCrudController::class, CreditTransactionCrudController::class], $controllers);
    }

    // A form field carries `<Entity>_<property>` as its id, so a highlight naming a property the screen stopped declaring points at nothing
    public function testEveryHighlightedFieldIsStillDeclaredByItsScreen(): void
    {
        foreach ($this->createProvider()->getGuidedProjects() as $project) {
            foreach ($project['steps'] as $index => $step) {
                if (!isset($step['highlight']) || !preg_match('/^#(CreditPack|CreditTransaction)_(\w+)$/', $step['highlight'], $matches)) {
                    continue;
                }

                $source = (string) file_get_contents(\dirname(__DIR__, 2) . '/src/Controller/Management/' . $matches[1] . 'CrudController.php');
                preg_match_all("/Field::new\\('(\\w+)'/", $source, $fields);

                $this->assertContains($matches[2], $fields[1], sprintf('Step %d of "%s" highlights a property %s no longer declares', $index, $project['slug'], $matches[1]));
            }
        }
    }

    // A label or description with no translation reads as its own key in the panel, and a narration missing from one locale is only ever heard - never read - so nothing else catches it
    public function testEveryKeyIsTranslatedInEveryLocale(): void
    {
        foreach (['en', 'fr', 'es'] as $locale) {
            $translated = $this->translatedKeys('purchasecredits', $locale);
            $narrated = $this->translatedKeys('purchasecredits_narration', $locale);

            foreach ($this->createProvider()->getGuidedProjects() as $project) {
                foreach ([$project, ...$project['steps']] as $item) {
                    $this->assertContains($item['label'], $translated, sprintf('"%s" is missing from the %s catalogue', $item['label'], $locale));
                    $this->assertContains($item['description'], $translated, sprintf('"%s" is missing from the %s catalogue', $item['description'], $locale));
                    if (isset($item['narration'])) {
                        $this->assertContains($item['narration'], $narrated, sprintf('"%s" is missing from the %s narration catalogue', $item['narration'], $locale));
                    }
                }
            }
        }
    }

    private function translatedKeys(string $domain, string $locale): array
    {
        $xliff = new \DOMDocument();
        $xliff->load(\dirname(__DIR__, 2) . '/translations/' . $domain . '.' . $locale . '.xlf');

        $keys = [];
        foreach ($xliff->getElementsByTagName('source') as $source) {
            $keys[] = $source->textContent;
        }

        return $keys;
    }
}
