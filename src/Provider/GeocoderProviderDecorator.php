<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Collection;
use Geocoder\Provider\Provider as GeocoderProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Override;

final class GeocoderProviderDecorator implements Provider
{
    public function __construct(
        private readonly GeocoderProvider $provider,
        private readonly string $providerId,
        private readonly string|null $title = null,
    ) {
    }

    #[Override]
    public function title(): string
    {
        return $this->title ?? $this->providerId;
    }

    #[Override]
    public function providerId(): string
    {
        return $this->providerId;
    }

    #[Override]
    public function type(): string
    {
        return $this->getName();
    }

    #[Override]
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        return $this->provider->geocodeQuery($query);
    }

    #[Override]
    public function reverseQuery(ReverseQuery $query): Collection
    {
        return $this->provider->reverseQuery($query);
    }

    #[Override]
    public function getName(): string
    {
        return $this->provider->getName();
    }

    public function provider(): GeocoderProvider
    {
        return $this->provider;
    }
}
