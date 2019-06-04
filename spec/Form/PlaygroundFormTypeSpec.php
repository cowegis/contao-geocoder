<?php

namespace spec\Cowegis\ContaoGeocoder\Form;

use Cowegis\ContaoGeocoder\Form\PlaygroundFormType;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PlaygroundFormTypeSpec extends ObjectBehavior
{
    public function let(Geocoder $geocoder) : void
    {
        $this->beConstructedWith($geocoder);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(PlaygroundFormType::class);
    }

    public function it_builds_form(FormBuilderInterface $builder) : void
    {
        $builder->add('address', TextType::class, Argument::any())
            ->willReturn($builder)
            ->shouldBeCalledOnce();

        $builder->add('provider', ChoiceType::class, Argument::any())
            ->willReturn($builder)
            ->shouldBeCalledOnce();

        $builder->add('submit', SubmitType::class, Argument::any())
            ->willReturn($builder)
            ->shouldBeCalledOnce();

        $this->buildForm($builder, []);
    }
}
