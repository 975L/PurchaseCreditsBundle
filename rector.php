<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\ClassMethod\AddParamBasedOnParentClassMethodRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

// The same sets a site gets from SymfonyMigrate.sh, so the bundle and the applications installing it are modernised by the same rules. withPhpSets() and withComposerBased() read their targets from composer.json, so no PHP or Symfony version is named here
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    // The cache lives in the repository rather than in sys_get_temp_dir(), one directory shared by every repository on the machine: a run here no longer competes with, nor empties, the cache of the other repositories. bin/ci.sh keeps its cold cache by leaving this directory out of the copy
    ->withCache(cacheDirectory: __DIR__ . '/.rector.cache')
    ->withComposerBased(symfony: true, doctrine: true)
    // Symfony, Doctrine and Sensio annotations turned into attributes: withComposerBased() only brings the version upgrades, and without this set a @Route or an @Assert left in a docblock is a mere comment Symfony 8 ignores
    ->withAttributesSets(symfony: true, doctrine: true, sensiolabs: true)
    // Two rules are dropped rather than followed: a readonly class can only be extended by another readonly one, which closes the door these bundles are built to leave open - a site overriding a service would have to make its own readonly too, and could then no longer hold state of its own; and copying a parent's parameters after a variadic $args does not compile, while that variadic is deliberate in the CrudControllers, where it absorbs EasyAdmin's signature changes without the bundle having to follow them
    ->withSkip([
        AddParamBasedOnParentClassMethodRector::class,
        ReadOnlyClassRector::class,
    ]);
