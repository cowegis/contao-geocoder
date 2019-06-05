<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;

final class ApplicationConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @var mixed[] */
    private $providers;

    /** @param mixed[] $providers */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /** @return mixed[][] */
    public function getIterator() : iterable
    {
        return new ArrayIterator($this->providers);
    }
}