<?php

namespace spec\Cowegis\ContaoGeocoder\Provider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use Cowegis\ContaoGeocoder\Provider\GeocoderFactory;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use IteratorAggregate;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

final class GeocoderFactorySpec extends ObjectBehavior
{
    public function let(ConfigProvider $configProvider, ProviderFactory $providerFactory) : void
    {
        $configProvider->implement(IteratorAggregate::class);

        $this->beConstructedWith($configProvider, $providerFactory);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(GeocoderFactory::class);
    }

    public function it_creates_geocoder_with_providers_for_given_configuration(
        ConfigProvider $configProvider,
        ProviderFactory $providerFactory,
        Provider $foo,
        Provider $bar
    ) : void{
        $configProvider->getIterator()->willReturn(
            new ArrayIterator([['id' => 'foo', 'type' => 'google_maps'], ['id' => 'bar', 'type' => 'nominatim']])
        );

        $foo->providerId()->willReturn('foo');
        $bar->providerId()->willReturn('bar');

        $providerFactory->create('google_maps', Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($foo);

        $providerFactory->create('nominatim', Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($bar);

        $geocoder = $this->__invoke();

        $geocoder->getIterator()->shouldContain($foo);
        $geocoder->getIterator()->shouldContain($bar);
    }
}
