<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Form\Block;

use c975L\PurchaseCreditsBundle\Form\Block\PacksBlockType;
use c975L\PurchaseCreditsBundle\Tests\Form\FormFieldsTestCase;
use c975L\UiBundle\Service\BlockAnchorSlugger;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PacksBlockTypeTest extends FormFieldsTestCase
{
    private function type(): PacksBlockType
    {
        return new PacksBlockType(new BlockAnchorSlugger(new AsciiSlugger()));
    }

    // BlockType translates the embedded data form in the "ui" domain: a type forgetting this renders every one of its labels raw
    public function testTheTypeIsTranslatedInThisBundlesCatalogue(): void
    {
        $resolver = new OptionsResolver();
        $this->type()->configureOptions($resolver);

        $this->assertSame('purchasecredits', $resolver->resolve()['translation_domain']);
    }

    // Only the head of the section: the packs are the back office's rows, read at render time, so a block can never show a price the checkout would not charge
    public function testTheBlockOnlyHoldsItsSectionHead(): void
    {
        $this->assertSame(['anchor', 'title', 'content', 'background'], array_keys($this->buildFields($this->type())));
    }
}
