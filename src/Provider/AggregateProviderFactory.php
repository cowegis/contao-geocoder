<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Exception\ProviderNotRegistered;
use function array_key_exists;
use function array_keys;

final class AggregateProviderFactory implements ProviderFactory
{
    /** @var array<string, ProviderTypeFactory> */
    private $factories = [];

    public function register(ProviderTypeFactory $factory) : void
    {
        $this->factories[$factory->name()] = $factory;
    }

    public function supports(string $type, string $feature) : bool
    {
        if (! array_key_exists($type, $this->factories)) {
            return false;
        }

        return $this->factories[$type]->supports($feature);
    }

    /** {@inheritDoc} */
    public function create(string $type, array $config) : Provider
    {
        if (! array_key_exists($type, $this->factories)) {
            throw ProviderNotRegistered::create($type);
        }

        return $this->factories[$type]->create($config);
    }

    /** {@inheritDoc} */
    public function typeNames() : array
    {
        return array_keys($this->factories);
    }
}
