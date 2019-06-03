<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\SubmitButton;

final class PlaygroundFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder
            ->add('address', TextareaType::class)
            ->add(SubmitButton::class);
    }
}
