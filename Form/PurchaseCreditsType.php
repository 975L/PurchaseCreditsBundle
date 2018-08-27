<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\PurchaseCreditsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * PurchaseCredits FormType
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class PurchaseCreditsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('credits', ChoiceType::class, array(
                'expanded' => true,
                'multiple' => false,
                'label' => 'label.number_credits',
                'required' => true,
                'data' => $options['purchaseCreditsConfig']['credits'],
                'choices' => $options['purchaseCreditsConfig']['pricesChoice']
                ))
            ->add('userIp', TextType::class, array(
                'label' => 'label.ip',
                'translation_domain' => 'services',
                'required' => true,
                'attr' => array(
                    'readonly' => true,
                )))
        ;
        //GDPR
        if ($options['purchaseCreditsConfig']['gdpr']) {
            $builder
                ->add('gdpr', CheckboxType::class, array(
                    'label' => 'text.gdpr',
                    'translation_domain' => 'services',
                    'required' => true,
                    'mapped' => false,
                    ))
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'intention' => 'purchaseCreditsForm',
            'translation_domain' => 'purchaseCredits',
        ));

        $resolver->setRequired('purchaseCreditsConfig');
    }
}
