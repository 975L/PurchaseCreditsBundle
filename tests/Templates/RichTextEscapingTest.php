<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Templates;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

// What an editor writes on the block reaches a visitor's page through the sanitizer, never as it was typed
class RichTextEscapingTest extends TestCase
{
    public function testNoTemplatePrintsAnythingRaw(): void
    {
        $raw = [];
        foreach (new Finder()->files()->in(\dirname(__DIR__, 2) . '/templates')->name('*.twig') as $file) {
            $source = (string) preg_replace('/\{#.*?#\}/s', '', $file->getContents());
            if (1 === preg_match('/\|\s*raw\b/', $source)) {
                $raw[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $raw);
    }

    // Under the sanitizer CoreBundle declares
    public function testTheBlockParagraphGoesThroughTheSanitizer(): void
    {
        $this->assertStringContainsString("content|sanitize_html('c975l.rich')", (string) file_get_contents(\dirname(__DIR__, 2) . '/templates/blocks/Packs.html.twig'));
    }
}
