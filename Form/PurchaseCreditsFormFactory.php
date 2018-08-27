<?php
/*
 * (c) 2018: 975L <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\ContactFormBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use c975L\ContactFormBundle\Entity\ContactForm;
use c975L\ContactFormBundle\Event\ContactFormEvent;
use c975L\ContactFormBundle\Form\ContactFormType;
use c975L\ContactFormBundle\Form\ContactFormFactoryInterface;

/**
 * FormFactory class
 * @author Laurent Marquet <laurent.marquet@laposte.net>
 * @copyright 2018 975L <contact@975l.com>
 */
class ContactFormFactory implements ContactFormFactoryInterface
{
    /**
     * Stores container
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        FormFactoryInterface $formFactory
    )
    {
        $this->container = $container;
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, ContactForm $contactForm, ContactFormEvent $event)
    {
        $contactFormConfig = array();

        if ('display' === $name) {
            $contactFormConfig = array(
                'receiveCopy' => $event->getReceiveCopy(),
                'gdpr' => $this->container->getParameter('c975_l_contact_form.gdpr'),
            );
        }

        return $this->formFactory->create(ContactFormType::class, $contactForm, array('contactFormConfig' => $contactFormConfig));
    }
}
