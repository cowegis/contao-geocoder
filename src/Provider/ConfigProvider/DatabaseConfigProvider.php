<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use IteratorAggregate;

final class DatabaseConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @var ProviderRepository */
    private $repository;

    public function __construct(ProviderRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return mixed[][] */
    public function getIterator() : iterable
    {
        $collection = $this->repository->findAll();
        if ($collection) {
            return new ArrayIterator($collection->fetchAll());
        }

        return new ArrayIterator();
    }
}
