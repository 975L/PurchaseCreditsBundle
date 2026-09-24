<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Each repository is one copy-paste away from the other: one naming the other's entity in its parent constructor reads and writes the wrong table - the ledger summed over the packs - which nothing else here would catch
class RepositoryBindingTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function repositories(): iterable
    {
        foreach (glob(\dirname(__DIR__, 2) . '/src/Repository/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            yield $name => [$name, (string) file_get_contents($file)];
        }
    }

    // "CreditPackRepository" manages "CreditPack": the name is the contract, and the parent constructor is where it is honoured
    #[DataProvider('repositories')]
    public function testEachRepositoryManagesTheEntityItsNameAnnounces(string $name, string $source): void
    {
        $entity = substr($name, 0, -\strlen('Repository'));

        $this->assertStringContainsString(
            sprintf('parent::__construct($registry, %s::class);', $entity),
            $source,
            sprintf('%s does not hand %s::class to its parent constructor, so it reads another entity\'s table.', $name, $entity),
        );
    }

    #[DataProvider('repositories')]
    public function testEachRepositoryIsAServiceEntityRepository(string $name, string $source): void
    {
        $class = 'c975L\\PurchaseCreditsBundle\\Repository\\' . $name;

        $this->assertTrue(is_subclass_of($class, ServiceEntityRepository::class), sprintf('%s is not a ServiceEntityRepository, so Doctrine will not autowire it.', $class));
    }

    // The generator's commented-out findByExampleField()/findOneBySomeField(): dead weight that every reader has to skip past, and that phpcs counts as code
    #[DataProvider('repositories')]
    public function testNoRepositoryStillCarriesTheGeneratorExamples(string $name, string $source): void
    {
        $this->assertStringNotContainsString('findByExampleField', $source, sprintf('%s still carries the maker\'s commented-out example methods.', $name));
    }
}
