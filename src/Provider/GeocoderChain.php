<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use AppendIterator;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use IteratorAggregate;
use IteratorIterator;

final class GeocoderChain implements IteratorAggregate, Geocoder
{
    /** @var Geocoder[] */
    private $geocoders = [];

    /**
     * @param Geocoder[] $geocoders
     */
    public function __construct(iterable $geocoders)
    {
        $this->geocoders = $geocoders;
    }

    public function using(string $providerId) : Provider
    {
        foreach ($this->geocoders as $geocoder) {
            try {
                return $geocoder->using($providerId);
            } catch (ProviderNotRegistered $e) {
                // do nothing
            }
        }

        throw ProviderNotRegistered::create($providerId);
    }

    public function geocodeQuery(GeocodeQuery $query) : Collection
    {
        foreach ($this->geocoders as $geocoder) {
            try {
                return $geocoder->geocodeQuery($query);
            } catch (ProviderNotRegistered $e) {
                // do nothing
            }
        }

        throw new ProviderNotRegistered('No default provider registered');
    }

    public function reverseQuery(ReverseQuery $query) : Collection
    {
        foreach ($this->geocoders as $geocoder) {
            try {
                return $geocoder->reverseQuery($query);
            } catch (ProviderNotRegistered $e) {
                // do nothing
            }
        }

        throw new ProviderNotRegistered('No default provider registered');
    }

    public function getName() : string
    {
        return 'cowegis_chain';
    }

    /** @return Provider[] */
    public function getIterator() : iterable
    {
        $iterator = new AppendIterator();

        foreach ($this->geocoders as $geocoder) {
            $iterator->append(new IteratorIterator($geocoder));
        }

        return $iterator;
    }
}
