<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests;

use PHPUnit\Framework\TestCase;

// Guards skills/*/SKILL.md, the bundle's documentation for the coding agents of the apps installing it (see the README's "AI agent skills" section) - prose an agent trusts, so what is checkable in it is checked here: a renamed class, route, config slug, block kind or command otherwise leaves the file compiling, the tests green and the agent confidently wrong
class SkillsTest extends TestCase
{
    // What this bundle names its own things, the only part of this test that differs from one bundle to the next
    private const string SLUG_PATTERN = '#^purchasecredits-[a-z-]+$#';

    private const string NAME_PATTERN = '#^[a-z][a-z0-9]*(_[a-z0-9]+)+(\([^)]*\))?$#';

    private const string COMMAND_PATTERN = '/c975l:purchasecredits:[a-z-:]+/';

    private const string COMPONENT_PATTERN = '/<twig:c975LPurchaseCredits:([A-Za-z:]+)/';

    /** @return array<string, string> */
    private function loadSkills(): array
    {
        $files = glob($this->root() . '/skills/*/SKILL.md') ?: [];
        $this->assertNotSame([], $files, 'No SKILL.md found');

        $skills = [];
        foreach ($files as $file) {
            $skills[basename(\dirname($file))] = (string) file_get_contents($file);
        }

        return $skills;
    }

    private function root(): string
    {
        return \dirname(__DIR__);
    }

    /** @return list<string> */
    private function backtickedTokens(string $content, string $pattern): array
    {
        preg_match_all('/`([^`\n]+)`/', $content, $matches);

        return array_values(array_unique(array_filter($matches[1], static fn (string $token): bool => 1 === preg_match($pattern, $token))));
    }

    /** @return list<string> */
    private function sources(string $directory = '/src'): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . $directory));
        foreach ($iterator as $file) {
            if ('php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return list<string> */
    private function matchesInSources(string $pattern, string $directory = '/src'): array
    {
        $found = [];
        foreach ($this->sources($directory) as $file) {
            preg_match_all($pattern, (string) file_get_contents($file), $matches);
            $found = array_merge($found, $matches[1]);
        }

        return $found;
    }

    // The file a class name stands for, src/ being flat enough for the basename to be unique - a relative namespace ("Service\UploadLimits") is resolved as written, a bare name is looked up
    private function findClassFile(string $name): ?string
    {
        $name = str_replace('\\', '/', $name);
        $path = $this->root() . '/src/' . $name . '.php';
        if (is_file($path)) {
            return $path;
        }

        foreach ($this->sources() as $file) {
            if (basename($file) === basename($name) . '.php') {
                return $file;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function routeNames(): array
    {
        return $this->matchesInSources("/#\[(?:Admin)?Route\([^)]*name: '([a-z_]+)'/");
    }

    // The block kinds tagged in services.yaml, plus the singletons a CRUD controller holds as its own KIND constant
    private function blockKinds(): array
    {
        preg_match_all('/^\s+kind: ([a-z_]+)$/m', (string) file_get_contents($this->root() . '/config/services.yaml'), $matches);

        return array_merge($matches[1], $this->matchesInSources("/const string KIND = '([a-z_]+)'/"));
    }

    /** @return list<string> */
    private function twigFunctions(): array
    {
        return $this->matchesInSources("/#\[AsTwigFunction\('([a-z_]+)'/");
    }

    /** @return list<string> */
    private function commandNames(): array
    {
        // Any c975l command name written as a literal - an AsCommand attribute, a NAME constant, a maker's getCommandName()
        return $this->matchesInSources("/'(c975l:[a-z0-9:-]+)'/");
    }

    // Whether a name this bundle doesn't declare itself is one the ecosystem does somewhere - a config slug of ConfigBundle's, a block context of UiBundle's, a Twig option a template passes. The installed vendor tree is what makes it visible, so a checkout without dependencies lets those through rather than failing on what it cannot see
    private function isDeclaredSomewhere(string $name): bool
    {
        static $haystack = null;

        if (null === $haystack) {
            // The package root is this bundle's own directory, unless it ships several of them (core-bundle), where the vendor tree and the sibling bundles both sit one level up
            $package = is_file($this->root() . '/composer.json') ? $this->root() : \dirname($this->root());
            $directories = array_filter(array_merge(
                [$package . '/vendor/c975l'],
                glob($package . '/*/src') ?: [],
                glob($package . '/*/templates') ?: [],
                glob($package . '/*/config') ?: [],
                glob($package . '/*/tests') ?: [],
                [$this->root() . '/src', $this->root() . '/templates', $this->root() . '/config', $this->root() . '/tests'],
            ), is_dir(...));

            if ([] === $directories) {
                return true;
            }

            $haystack = '';
            foreach (array_unique($directories) as $directory) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
                foreach ($files as $file) {
                    if (\in_array($file->getExtension(), ['php', 'yaml', 'json', 'twig'], true)) {
                        $haystack .= file_get_contents($file->getPathname());
                    }
                }
            }
        }

        return str_contains($haystack, $name);
    }

    // The frontmatter is what makes a SKILL.md loadable by an agent, and the name what another package's skill refers it by, so it carries the vendor prefix and matches its own directory
    public function testEverySkillCarriesTheExpectedFrontmatter(): void
    {
        foreach ($this->loadSkills() as $directory => $content) {
            $this->assertSame(1, preg_match('/\A---\n(.*?)\n---\n/s', $content, $matches), sprintf('%s has no frontmatter', $directory));

            $frontmatter = $matches[1];
            $this->assertSame(1, preg_match('/^name: (.+)$/m', $frontmatter, $name), sprintf('%s has no name', $directory));
            $this->assertSame($directory, trim($name[1]), 'A skill\'s name and its directory must match');
            $this->assertStringStartsWith('c975l-', $directory, 'A skill installed in someone else\'s project is prefixed, or it collides with theirs');

            $this->assertSame(1, preg_match('/^description: (.+)$/m', $frontmatter, $description), sprintf('%s has no description', $directory));
            $this->assertStringContainsString('Triggers on:', $description[1], 'A description without triggers never loads the skill');
        }
    }

    // Every path the skill sends an agent reading to
    public function testKeySourcePathsExist(): void
    {
        foreach ($this->loadSkills() as $directory => $content) {
            $this->assertSame(1, preg_match('/^\*\*Key source paths\*\*.*$/m', $content, $matches), sprintf('%s lists no key source path', $directory));

            foreach ($this->backtickedTokens($matches[0], '#^[a-zA-Z0-9_/.-]+$#') as $path) {
                $this->assertFileExists($this->root() . '/' . $path);
            }
        }
    }

    // A snake_case name is a route, a block kind or a Twig function - all three are read from the sources, so renaming one fails here
    public function testEveryNameQuotedIsARouteABlockKindOrATwigFunction(): void
    {
        $known = array_merge($this->routeNames(), $this->blockKinds(), $this->twigFunctions());

        foreach ($this->loadSkills() as $directory => $content) {
            foreach ($this->backtickedTokens($content, self::NAME_PATTERN) as $name) {
                // A Twig function is quoted with its call, a route or a kind on its own
                $name = (string) preg_replace('/\(.*$/', '', $name);

                if (\in_array($name, $known, true)) {
                    continue;
                }

                $this->assertTrue($this->isDeclaredSomewhere($name), sprintf('%s quotes "%s", which neither this bundle nor the rest of the ecosystem declares', $directory, $name));
            }
        }
    }

    // The routes a code sample generates a url with
    public function testEveryRouteGeneratedInASampleExists(): void
    {
        $routes = $this->routeNames();

        foreach ($this->loadSkills() as $directory => $content) {
            preg_match_all("/path\('([a-z_]+)'/", $content, $matches);
            foreach (array_unique($matches[1]) as $name) {
                $this->assertContains($name, $routes, sprintf('%s generates a url for "%s", which is no route of this bundle', $directory, $name));
            }
        }
    }

    public function testEveryConfigSlugQuotedIsDeclared(): void
    {
        // This bundle declares no config key of its own, which leaves the ecosystem's
        $configs = $this->root() . '/config/configs.json';
        $declared = is_file($configs) ? array_column(json_decode((string) file_get_contents($configs), true, 512, \JSON_THROW_ON_ERROR), 'slug') : [];

        foreach ($this->loadSkills() as $directory => $content) {
            foreach ($this->backtickedTokens($content, self::SLUG_PATTERN) as $slug) {
                if (\in_array($slug, $declared, true)) {
                    continue;
                }

                // A satellite reads plenty of slugs ConfigBundle declares - "site-url", "site-role-editor" - which are no less real for not being this bundle's own
                $this->assertTrue($this->isDeclaredSomewhere($slug), sprintf('%s quotes "%s", which no configs.json of the ecosystem declares', $directory, $slug));
            }
        }
    }

    public function testEveryCommandQuotedExists(): void
    {
        $commands = $this->commandNames();

        foreach ($this->loadSkills() as $directory => $content) {
            preg_match_all(self::COMMAND_PATTERN, $content, $matches);
            foreach (array_unique($matches[0]) as $command) {
                $this->assertContains($command, $commands, sprintf('%s quotes "%s", which is no command of this bundle', $directory, $command));
            }
        }
    }

    // A class of this bundle quoted by its name, on its own or carrying a constant or a method - the ecosystem's other packages and the app's own classes are skipped, both being out of this repository's reach
    public function testEveryClassMemberQuotedExists(): void
    {
        foreach ($this->loadSkills() as $directory => $content) {
            foreach ($this->backtickedTokens($content, '#^(?!c975L\\\\|App\\\\)[A-Z][A-Za-z]*(\\\\[A-Z][A-Za-z]*)*(::[A-Za-z_]+(\(\))?)?$#') as $token) {
                // A bare name is only checked for existing somewhere: it may well be Symfony's or another package's, where a file of this bundle is not owed
                if (!str_contains($token, '\\') && !str_contains($token, '::')) {
                    $this->assertTrue($this->isDeclaredSomewhere($token), sprintf('%s quotes "%s", which nothing in the ecosystem names', $directory, $token));

                    continue;
                }

                [$class, $member] = array_pad(explode('::', $token, 2), 2, null);

                // Only what this bundle could hold: a namespaced name is checked when its first segment is a real folder of src/ ("Service\UploadLimits"), a bare one when src/ holds a file of that name at all - Symfony's own "Assert\Email" or "FormBuilder::add()" are neither, and are no business of this repository
                $file = $this->findClassFile($class);
                if (null === $file && !is_dir($this->root() . '/src/' . strstr($class . '\\', '\\', true))) {
                    continue;
                }

                $this->assertNotNull($file, sprintf('%s quotes "%s", which no class of src/ answers to', $directory, $class));

                if (null === $member) {
                    continue;
                }

                $source = (string) file_get_contents($file);

                // A constant may carry a type since PHP 8.3 ("const string AUTOMATIC_LATEST"), so the name is matched rather than a fixed prefix - a plain substring reads the typed one as absent
                $needle = str_ends_with($member, '()')
                    ? '/function\s+' . preg_quote(substr($member, 0, -2), '/') . '\s*\(/'
                    : '/const\s+(?:[\\\\|?A-Za-z_][\\\\|?A-Za-z0-9_]*\s+)?' . preg_quote($member, '/') . '\b/';
                $this->assertMatchesRegularExpression($needle, $source, sprintf('%s quotes "%s", which %s does not hold', $directory, $token, basename($file)));
            }
        }
    }

    // A component is a template of its own, named after the tag
    public function testEveryComponentQuotedExists(): void
    {
        foreach ($this->loadSkills() as $directory => $content) {
            preg_match_all(self::COMPONENT_PATTERN, $content, $matches);
            foreach (array_unique($matches[1]) as $component) {
                $this->assertFileExists($this->root() . '/templates/components/' . str_replace(':', '/', $component) . '.html.twig', sprintf('%s renders a component this bundle does not ship', $directory));
            }
        }
    }
}
