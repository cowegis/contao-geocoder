<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use AppendIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;
use IteratorIterator;
use Traversable;

final class ConfigProviderChain implements IteratorAggregate, ConfigProvider
{
    /** @var iterable|ConfigProvider[] */
    private $configProviders;

    /**
     * @param ConfigProvider[] $configProviders
     */
    public function __construct(iterable $configProviders)
    {
        $this->configProviders = $configProviders;
    }

    /** @return Traversable|mixed[][] */
    public function getIterator() : iterable
    {
        $iterator = new AppendIterator();

        foreach ($this->configProviders as $configProvider) {
            $iterator->append(new IteratorIterator($configProvider));
        }

        return $iterator;
    }
}
