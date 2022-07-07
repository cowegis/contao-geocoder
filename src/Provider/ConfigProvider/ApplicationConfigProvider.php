<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<array{type: string, title: ?string, id: string}> */
final class ApplicationConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @var list<array{type: string, title: ?string, id: string}> */
    private $providers;

    /** @param list<array{type: string, title: ?string, id: string}> $providers */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /** @return Traversable<array{type: string, title: ?string, id: string}> */
    public function getIterator(): iterable
    {
        return new ArrayIterator($this->providers);
    }
}
