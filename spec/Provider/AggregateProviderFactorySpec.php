<?php

namespace spec\Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Provider\AggregateProviderFactory;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use Geocoder\Exception\ProviderNotRegistered;
use PhpSpec\ObjectBehavior;

final class AggregateProviderFactorySpec extends ObjectBehavior
{
    public function let(ProviderTypeFactory $typeFactory) : void
    {
        $typeFactory->name()->willReturn('foo');
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(AggregateProviderFactory::class);
    }

    public function it_it_a_provider_factory() : void
    {
        $this->shouldBeAnInstanceOf(ProviderFactory::class);
    }

    public function it_registers_provider_type_factories(ProviderTypeFactory $typeFactory) : void
    {
        $this->register($typeFactory);

        $this->typeNames()->shouldReturn(['foo']);
    }

    public function it_checks_which_feature_a_type_factory_supports(ProviderTypeFactory $typeFactory) : void
    {
        $this->register($typeFactory);

        $typeFactory->supports(Provider::FEATURE_REVERSE)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->supports('foo', Provider::FEATURE_REVERSE)->shouldReturn(true);

        $typeFactory->supports(Provider::FEATURE_ADDRESS)
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->supports('foo', Provider::FEATURE_ADDRESS)->shouldReturn(false);
    }

    public function it_doesnt_support_features_of_unkown_type_factory(ProviderTypeFactory $typeFactory) : void
    {
        $this->register($typeFactory);

        $this->supports('bar', Provider::FEATURE_REVERSE)->shouldReturn(false);
    }

    public function it_delegates_creation_to_type_factory(ProviderTypeFactory $typeFactory) : void
    {
        $this->register($typeFactory);

        $typeFactory->create([])->shouldBeCalled();
        $this->create('foo', []);
    }

    public function it_throws_provider_not_registered_if_type_is_not_supported() : void
    {
        $this->shouldThrow(ProviderNotRegistered::class)->during('create', ['foo', []]);
    }
}
