<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use IteratorAggregate;
use Traversable;
use function count;
use function sprintf;
use function str_repeat;

/** @implements IteratorAggregate<array{type: string, title: ?string, id: string}> */
final class DatabaseConfigProvider implements IteratorAggregate, ConfigProvider
{
    /** @var ProviderRepository */
    private $repository;

    /** @var ProviderFactory */
    private $providerFactory;

    /** @var ContaoFramework */
    private $framework;

    public function __construct(
        ProviderRepository $repository,
        ProviderFactory $providerFactory,
        ContaoFramework $framework
    ){
        $this->repository      = $repository;
        $this->providerFactory = $providerFactory;
        $this->framework       = $framework;
    }

    /** @return Traversable<array{type: string, title: ?string, id: string}> */
    public function getIterator() : Traversable
    {
        $types = $this->providerFactory->typeNames();
        if (count($types) === 0) {
            return new ArrayIterator();
        }

        /** @psalm-suppress InternalMethod */
        $this->framework->initialize();
        $collection = $this->repository->findBy(
            [sprintf('.type IN (?%s)', str_repeat(',?', count($types) - 1))],
            $types,
            ['order' => 'FIELD( .isDefault, \'1\',\'\')']
        );

        if ($collection !== null) {
            return new ArrayIterator($collection->fetchAll());
        }

        return new ArrayIterator();
    }
}
