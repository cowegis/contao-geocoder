<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Collection;
use Geocoder\Provider\Provider as GeocoderProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class GeocoderProviderDecorator implements Provider
{
    /** @var GeocoderProvider */
    private $provider;

    /** @var string */
    private $id;

    /** @var string|null */
    private $title;

    public function __construct(GeocoderProvider $provider, string $providerId, ?string $title = null)
    {
        $this->provider = $provider;
        $this->id       = $providerId;
        $this->title    = $title;
    }

    public function title() : string
    {
        return $this->title ?: $this->id();
    }

    public function id() : string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->getName();
    }

    public function geocodeQuery(GeocodeQuery $query) : Collection
    {
        return $this->provider->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query) : Collection
    {
        return $this->provider->reverseQuery($query);
    }

    public function getName() : string
    {
        return $this->provider->getName();
    }
}
