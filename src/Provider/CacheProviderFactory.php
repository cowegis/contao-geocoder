<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Override;
use Psr\SimpleCache\CacheInterface;

final class CacheProviderFactory implements ProviderFactory
{
    use GeocoderProviderDecoratorFactory;

    public function __construct(private readonly ProviderFactory $factory, private readonly CacheInterface $cache)
    {
    }

    #[Override]
    public function register(ProviderTypeFactory $factory): void
    {
        $this->factory->register($factory);
    }

    #[Override]
    public function supports(string $type, string $feature): bool
    {
        return $this->factory->supports($type, $feature);
    }

    /** {@inheritDoc} */
    #[Override]
    public function typeNames(): array
    {
        return $this->factory->typeNames();
    }

    /** {@inheritDoc} */
    #[Override]
    public function create(string $type, array $config): Provider
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
