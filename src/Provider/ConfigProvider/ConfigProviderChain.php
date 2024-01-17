<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use AppendIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;
use IteratorIterator;
use Traversable;

/**
 * @psalm-type TProviderConfig = array{
 *      type: string,
 *      title: ?string,
 *      id: string,
 *      cache:int|numeric-string|bool,
 *      cache_ttl: int|numeric-string,
 *      ...
 * }
 * @implements IteratorAggregate<TProviderConfig>
 */
final class ConfigProviderChain implements IteratorAggregate, ConfigProvider
{
    /** @param list<ConfigProvider> $configProviders */
    public function __construct(private readonly iterable $configProviders)
    {
    }

    /**
     * @return Traversable<TProviderConfig>
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getIterator(): Traversable
    {
        $iterator = new AppendIterator();

        foreach ($this->configProviders as $configProvider) {
            $iterator->append(new IteratorIterator($configProvider));
        }

        return $iterator;
    }
}
