<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use AppendIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;
use IteratorIterator;
use Traversable;

/** @implements IteratorAggregate<array{type: string, title: ?string, id: string}> */
final class ConfigProviderChain implements IteratorAggregate, ConfigProvider
{
    /** @var list<ConfigProvider> */
    private $configProviders;

    /**
     * @param list<ConfigProvider> $configProviders
     */
    public function __construct(iterable $configProviders)
    {
        $this->configProviders = $configProviders;
    }

    /** @return Traversable<array{type: string, title: ?string, id: string}> */
    public function getIterator(): iterable
    {
        $iterator = new AppendIterator();

        foreach ($this->configProviders as $configProvider) {
            $iterator->append(new IteratorIterator($configProvider));
        }

        return $iterator;
    }
}
