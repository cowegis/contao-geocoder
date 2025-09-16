<?php

declare(strict_types=1);

namespace spec\Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Provider\GeocoderProviderDecorator;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Provider\Provider as GeocoderProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use PhpSpec\ObjectBehavior;

final class GeocoderProviderDecoratorSpec extends ObjectBehavior
{
    private const string TITLE = 'foo';

    private const string ID = '4';

    public function let(GeocoderProvider $provider): void
    {
        $this->beConstructedWith($provider, self::ID, self::TITLE);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GeocoderProviderDecorator::class);
    }

    public function it_is_a_provider(): void
    {
        $this->shouldImplement(Provider::class);
    }

    public function it_has_a_provider_id(): void
    {
        $this->providerId()->shouldReturn(self::ID);
    }

    public function it_has_a_title(): void
    {
        $this->title()->shouldReturn(self::TITLE);
    }

    public function it_uses_provider_id_as_title_fallback(GeocoderProvider $provider): void
    {
        $this->beConstructedWith($provider, self::ID);

        $this->title()->shouldReturn(self::ID);
    }

    public function it_gets_provider_name_as_type(GeocoderProvider $provider): void
    {
        $provider->getName()->willReturn('google_maps');

        $this->getName()->shouldReturn('google_maps');
        $this->type()->shouldReturn('google_maps');
    }

    public function it_delegates_geocode_query(GeocoderProvider $provider): void
    {
        $query = GeocodeQuery::create('Berlin');

        $provider->geocodeQuery($query)->shouldBeCalledOnce();
        $this->geocodeQuery($query);
    }

    public function it_delegates_reverse_query(GeocoderProvider $provider): void
    {
        $query = ReverseQuery::fromCoordinates(0.0, 0.0);

        $provider->reverseQuery($query)->shouldBeCalledOnce();
        $this->reverseQuery($query);
    }
}
