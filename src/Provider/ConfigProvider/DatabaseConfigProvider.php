<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ConfigProvider;

use ArrayIterator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ConfigProvider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use IteratorAggregate;
use Override;
use Traversable;

use function count;
use function sprintf;
use function str_repeat;

/** @implements IteratorAggregate<array{type: string, title: ?string, id: string}> */
final class DatabaseConfigProvider implements IteratorAggregate, ConfigProvider
{
    public function __construct(
        private readonly ProviderRepository $repository,
        private readonly ProviderFactory $providerFactory,
        private readonly ContaoFramework $framework,
    ) {
    }

    /** @return Traversable<array{type: string, title: ?string, id: string}> */
    #[Override]
    public function getIterator(): Traversable
    {
        $types = $this->providerFactory->typeNames();
        if ($types === []) {
            return new ArrayIterator();
        }

        /** @psalm-suppress InternalMethod */
        $this->framework->initialize();
        $collection = $this->repository->findBy(
            [sprintf('.type IN (?%s)', str_repeat(',?', count($types) - 1))],
            $types,
            ['order' => 'FIELD( .isDefault, \'1\',\'\')'],
        );

        if ($collection instanceof Collection) {
            return new ArrayIterator($collection->fetchAll());
        }

        return new ArrayIterator();
    }
}
