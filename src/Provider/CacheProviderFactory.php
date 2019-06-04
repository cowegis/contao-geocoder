<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Cache\ProviderCache;
use Psr\SimpleCache\CacheInterface;

final class CacheProviderFactory implements ProviderFactory
{
    use GeocoderProviderDecoratorFactory;

    /** @var ProviderFactory */
    private $factory;

    /** @var CacheInterface */
    private $cache;

    public function __construct(ProviderFactory $factory, CacheInterface $cache)
    {
        $this->factory = $factory;
        $this->cache   = $cache;
    }

    public function register(ProviderTypeFactory $factory) : void
    {
        $this->factory->register($factory);
    }

    public function supports(string $type, string $feature) : bool
    {
        return $this->factory->supports($type, $feature);
    }

    /** {@inheritDoc} */
    public function typeNames() : array
    {
        return $this->factory->typeNames();
    }

    /** {@inheritDoc} */
    public function create(string $type, array $config) : Provider
    {
        $provider = $this->factory->create($type, $config);
        $isActive = (bool) ($config['cache'] ?? false);
        $lifeTime = (int) ($config['cache_ttl'] ?? 0);

        if (! $isActive || $lifeTime === 0) {
            return $provider;
        }

        return $this->createDecorator(new ProviderCache($provider, $this->cache, $lifeTime), $config);
    }
}
