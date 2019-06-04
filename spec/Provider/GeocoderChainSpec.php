<?php

namespace spec\Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\GeocoderChain;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

final class GeocoderChainSpec extends ObjectBehavior
{
    public function let(Geocoder $geocoderA, Geocoder $geocoderB) : void
    {
        $this->beConstructedWith([$geocoderA, $geocoderB]);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(GeocoderChain::class);
    }

    public function it_is_a_geocoder() : void
    {
        $this->shouldImplement(Geocoder::class);
    }

    public function it_uses_first_geocoder_by_default(Geocoder $geocoderA, Geocoder $geocoderB, Provider $provider) : void
    {
        $geocoderA->using('a')
            ->shouldBeCalledOnce()
            ->willReturn($provider);

        $geocoderB->using('a')->shouldNotBeCalled();

        $this->using('a')->shouldReturn($provider);
    }

    public function it_uses_first_non_failing_geocoder(Geocoder $geocoderA, Geocoder $geocoderB, Provider $provider) : void
    {
        $geocoderA->using('a')
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->using('a')
            ->shouldBeCalledOnce()
            ->willReturn($provider);

        $this->using('a')->shouldReturn($provider);
    }

    public function it_throws_provider_not_registered_exception_for_failing_using(Geocoder $geocoderA, Geocoder $geocoderB) : void
    {
        $geocoderA->using('a')
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->using('a')
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $this->shouldThrow(ProviderNotRegistered::class)->during('using', ['a']);
    }

    public function it_geocodes_using_first_geocoder_by_default(Geocoder $geocoderA, Geocoder $geocoderB, Collection $collection) : void
    {
        $query = GeocodeQuery::create('foo');

        $geocoderA->geocodeQuery($query)
            ->shouldBeCalledOnce()
            ->willReturn($collection);

        $geocoderB->geocodeQuery($query)->shouldNotBeCalled();

        $this->geocodeQuery($query)->shouldReturn($collection);
    }

    public function it_geocodes_using_first_non_failing_geocoder(Geocoder $geocoderA, Geocoder $geocoderB, Collection $collection) : void
    {
        $query = GeocodeQuery::create('foo');

        $geocoderA->geocodeQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->geocodeQuery($query)
            ->shouldBeCalledOnce()
            ->willReturn($collection);

        $this->geocodeQuery($query)->shouldReturn($collection);
    }

    public function it_throws_provider_not_registered_exception_for_failing_geocode_query(Geocoder $geocoderA, Geocoder $geocoderB) : void
    {
        $query = GeocodeQuery::create('foo');

        $geocoderA->geocodeQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->geocodeQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $this->shouldThrow(ProviderNotRegistered::class)->during('geocodeQuery', [$query]);
    }

    public function it_reverse_geocodes_using_first_geocoder_by_default(Geocoder $geocoderA, Geocoder $geocoderB, Collection $collection) : void
    {
        $query = ReverseQuery::fromCoordinates(0.0, 0.0);

        $geocoderA->reverseQuery($query)
            ->shouldBeCalledOnce()
            ->willReturn($collection);

        $geocoderB->reverseQuery($query)->shouldNotBeCalled();

        $this->reverseQuery($query)->shouldReturn($collection);
    }

    public function it_reverse_geocodes_using_first_non_failing_geocoder(Geocoder $geocoderA, Geocoder $geocoderB, Collection $collection) : void
    {
        $query = ReverseQuery::fromCoordinates(0.0, 0.0);

        $geocoderA->reverseQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->reverseQuery($query)
            ->shouldBeCalledOnce()
            ->willReturn($collection);

        $this->reverseQuery($query)->shouldReturn($collection);
    }

    public function it_throws_provider_not_registered_exception_for_failing_reverse_geocode_query(Geocoder $geocoderA, Geocoder $geocoderB) : void
    {
        $query = ReverseQuery::fromCoordinates(0.0, 0.0);

        $geocoderA->reverseQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $geocoderB->reverseQuery($query)
            ->shouldBeCalledOnce()
            ->willThrow(ProviderNotRegistered::class);

        $this->shouldThrow(ProviderNotRegistered::class)->during('reverseQuery', [$query]);
    }
}
