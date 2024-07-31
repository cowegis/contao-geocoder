<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use ArrayIterator;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Provider\Provider as GeocodeProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use IteratorAggregate;
use Traversable;

use function array_keys;

/** @implements IteratorAggregate<Provider> */
final class Geocoder implements GeocodeProvider, IteratorAggregate
{
    /** @var Provider[] */
    private array $providers = [];

    private Provider|null $defaultProvider = null;

    public function register(Provider $provider): void
    {
        if (! $this->defaultProvider instanceof Provider) {
            $this->defaultProvider = $provider;
        }

        $this->providers[$provider->providerId()] = $provider;
    }

    public function using(string $providerId): Provider
    {
        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        throw ProviderNotRegistered::create($providerId, array_keys($this->providers));
    }

    /** @return Traversable<Provider> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->providers);
    }

    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        return $this->defaultProvider()->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query): Collection
    {
        return $this->defaultProvider()->reverseQuery($query);
    }

    public function getName(): string
    {
        return 'cowegis_geocoder';
    }

    private function defaultProvider(): Provider
    {
        if (! $this->defaultProvider instanceof Provider) {
            throw new ProviderNotRegistered('No default provider registered');
        }

        return $this->defaultProvider;
    }
}
