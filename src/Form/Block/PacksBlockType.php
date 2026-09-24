<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Form\Block;

use c975L\UiBundle\Form\Block\HasAnchorFieldTrait;
use c975L\UiBundle\Form\Block\HasBackgroundFieldTrait;
use c975L\UiBundle\Form\TrixEditorType;
use c975L\UiBundle\Service\BlockAnchorSlugger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// The data sub-form of the "purchasecredits_packs" kind: only the head of the section, the packs being read live at render time (see CreditsExtension)
class PacksBlockType extends AbstractType
{
    use HasAnchorFieldTrait;
    use HasBackgroundFieldTrait;

    public function __construct(
        private readonly BlockAnchorSlugger $anchorSlugger,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addAnchorField($builder, $this->anchorSlugger);

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.block_title',
                'required' => false,
            ])
            ->add('content', TrixEditorType::class, [
                'label' => 'label.block_content',
                'required' => false,
            ])
        ;

        $this->addBackgroundField($builder);
    }

    // BlockType translates the embedded form in the "ui" domain, hence this bundle's own
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'purchasecredits',
        ]);
    }
}
