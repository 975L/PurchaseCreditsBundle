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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseCreditsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $credits = $options['data']->getCredits();

        $builder
            ->add('credits', ChoiceType::class, array(
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
                'data' => $credits,
                'choices' => $options['prices']
                ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'intention' => 'purchaseCreditsForm',
            'translation_domain' => 'purchaseCredits',
        ));

        $resolver->setRequired('prices');
    }
}