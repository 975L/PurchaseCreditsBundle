<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

// The fields a type adds, read off a recording builder: a real FormBuilder would want a factory, a dispatcher and a data mapper, none of which says anything about the screen
abstract class FormFieldsTestCase extends TestCase
{
    /** @return array<string, array{type: ?string, options: array<string, mixed>}> */
    protected function buildFields(AbstractType $type, array $options = []): array
    {
        $added = [];

        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $builder): FormBuilderInterface {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $builder;
        });

        $type->buildForm($builder, $options);

        return $added;
    }
}
