<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use IteratorAggregate;
use function count;
use function sprintf;
use function str_repeat;

final class DatabaseConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @var ProviderRepository */
    private $repository;

    /** @var ProviderFactory */
    private $providerFactory;

    public function __construct(ProviderRepository $repository, ProviderFactory $providerFactory)
    {
        $this->repository      = $repository;
        $this->providerFactory = $providerFactory;
    }

    /** @return mixed[][] */
    public function getIterator() : iterable
    {
        $types = $this->providerFactory->typeNames();
        if (count($types) === 0) {
            return new ArrayIterator();
        }

        $collection = $this->repository->findBy(
            [sprintf('.type IN (?%s)', str_repeat(',?', count($types) - 1))],
            $types,
            ['.isDefault \'1\',\'\'']
        );

        if ($collection) {
            return new ArrayIterator($collection->fetchAll());
        }

        return new ArrayIterator();
    }
}
