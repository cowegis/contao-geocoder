<?php

namespace spec\Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Provider\Provider as GeocoderProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
final class GeocoderSpec extends ObjectBehavior
{
    public function let(Provider $foo, Provider $bar) : void
    {
        $foo->providerId()->willReturn('foo');
        $bar->providerId()->willReturn('bar');
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(Geocoder::class);
    }

    public function it_is_a_geocoder_provider() : void
    {
        $this->shouldImplement(GeocoderProvider::class);
    }

    public function it_uses_first_registered_provider_as_default(Provider $foo, Provider $bar) : void
    {
        $this->register($foo);
        $this->register($bar);

        $query = GeocodeQuery::create('Berlin');

        $foo->geocodeQuery($query)->shouldBeCalledOnce();
        $bar->geocodeQuery(Argument::any())->shouldNotBeCalled();

        $this->geocodeQuery($query);
    }

    public function it_has_a_name() : void
    {
        $this->getName()->shouldReturn('cowegis_geocoder');
    }

    public function it_geocodes_query(Provider $foo) : void
    {
        $this->register($foo);

        $query = GeocodeQuery::create('Berlin');

        $foo->geocodeQuery($query)->shouldBeCalledOnce();
        $this->geocodeQuery($query);
    }

    public function it_calls_reverse_query(Provider $foo) : void
    {
        $this->register($foo);

        $query = ReverseQuery::fromCoordinates(0.0, 0.0);

        $foo->reverseQuery($query)->shouldBeCalledOnce();
        $this->reverseQuery($query);
    }

    public function it_iterates_over_providers(Provider $foo, Provider $bar) : void
    {
        $this->register($foo);
        $this->register($bar);

        $this->getIterator()->shouldContain($foo);
        $this->getIterator()->shouldContain($bar);
    }

    public function it_allows_using_a_specific_provider(Provider $foo, Provider $bar) : void
    {
        $this->register($foo);
        $this->register($bar);

        $this->using('bar')->shouldReturn($bar);
    }

    public function it_throws_provider_not_registered_if_none_provider_is_registered() : void
    {
        $this->shouldThrow(ProviderNotRegistered::class)
            ->during('geocodeQuery', [GeocodeQuery::create('Berlin')]);

        $this->shouldThrow(ProviderNotRegistered::class)
            ->during('reverseQuery', [ReverseQuery::fromCoordinates(0.0, 0.0)]);
    }

    public function it_throws_provider_not_registered_using_non_registered_provider(Provider $foo) : void
    {
        $this->register($foo);

        $this->using('foo')->shouldReturn($foo);
        $this->shouldThrow(ProviderNotRegistered::class)->during('using', ['bar']);
    }
}
