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

final class ConfigDrivenGeocoder implements IteratorAggregate, Geocoder
{
    /** @var GeocodeProvider */
    private $defaultProvider;

    /** @var array<string,Geocoder> */
    private $providers;

    /** @var mixed[][] */
    private $providerConfig;

    /** @var ProviderFactory */
    private $factory;

    /** @var bool */
    private $loaded = false;

    /** @param mixed[][] $providerConfig */
    public function __construct(
        ProviderFactory $factory,
        array $providerConfig,
        ?string $defaultProvider = null
    ) {
        $this->factory         = $factory;
        $this->defaultProvider = $defaultProvider;
        $this->providerConfig  = $providerConfig;
    }

    public function geocodeQuery(GeocodeQuery $query) : Collection
    {
        return $this->defaultProvider()->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query) : Collection
    {
        return $this->defaultProvider()->reverseQuery($query);
    }

    public function getName() : string
    {
        return 'cowegis_config';
    }

    public function using(string $providerId) : Provider
    {
        if (! isset($this->providerConfig[$providerId])) {
            throw ProviderNotRegistered::create($providerId);
        }

        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        $config                       = $this->providerConfig[$providerId];
        $this->providers[$providerId] = $this->factory->create(
            $config['type'],
            $config['config']
        );

        return $this->providers[$providerId];
    }

    /** {@inheritDoc} */
    public function getIterator() : iterable
    {
        if ($this->loaded) {
            return new ArrayIterator($this->providers);
        }

        foreach ($this->providerConfig as $providerId => $config) {
            if (isset($this->providers[$providerId])) {
                continue;
            }

            $this->providers[$providerId] = $this->factory->create(
                $config['type'],
                $config['config']
            );
        }

        return new ArrayIterator($this->providers);
    }

    private function defaultProvider() : GeocodeProvider
    {
        if ($this->defaultProvider === null) {
            throw new ProviderNotRegistered('No default provider registered');
        }

        return $this->using($this->defaultProvider);
    }
}
