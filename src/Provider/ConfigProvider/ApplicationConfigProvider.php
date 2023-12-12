<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<array{type: string, title: ?string, id: string}> */
final readonly class ApplicationConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @param list<array{type: string, title: ?string, id: string}> $providers */
    public function __construct(private array $providers)
    {
    }

    /** @return Traversable<array{type: string, title: ?string, id: string}> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->providers);
    }
}
