<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use ArrayIterator;
use Cowegis\ContaoGeocoder\Model\ProviderModel;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Provider\Provider as GeocodeProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use IteratorAggregate;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;

final class DatabaseDrivenGeocoder implements IteratorAggregate, Geocoder
{
    /** @var ProviderRepository */
    private $repository;

    /** @var RequestScopeMatcher */
    private $scopeMatcher;

    /** @var bool */
    private $loaded = false;

    /** @var Provider[]|array<string,Provider> */
    private $providers = [];

    /** @var ProviderFactory */
    private $factory;

    /** @var Provider|null */
    private $defaultProvider;

    public function __construct(
        ProviderFactory $factory,
        ProviderRepository $repository,
        RequestScopeMatcher $scopeMatcher
    ) {
        $this->repository   = $repository;
        $this->scopeMatcher = $scopeMatcher;
        $this->factory      = $factory;
    }

    public function using(string $providerId) : Provider
    {
        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        if ($this->loaded) {
            throw ProviderNotRegistered::create($providerId);
        }

        /** @var ProviderModel|null $model */
        $model = $this->repository->find((int) $providerId);
        if ($model === null) {
            throw ProviderNotRegistered::create($providerId);
        }

        $this->providers[$providerId] = $this->factory->create($model->type, $model->row());

        return $this->providers[$providerId];
    }

    public function geocodeQuery(GeocodeQuery $query) : Collection
    {
        return $this->defaultProvider()->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query) : Collection
    {
        return $this->defaultProvider()->reverseQuery($query);
    }

    public function getName() : string
    {
        return 'cowegis_database';
    }

    /** @return Provider[] */
    public function getIterator() : iterable
    {
        if ($this->loaded) {
            return new ArrayIterator($this->providers);
        }

        $this->loaded = true;
        $collection   = $this->repository->findAll();

        foreach ($collection ?: [] as $model) {
            $this->providers[$model->id] = $this->factory->create($model->type, $model->row());
        }

        return new ArrayIterator($this->providers);
    }

    private function defaultProvider() : GeocodeProvider
    {
        if ($this->defaultProvider !== null) {
            return $this->defaultProvider;
        }

        $model = $this->repository->findDefaultForScope($this->getScope());
        if ($model === null) {
            throw new ProviderNotRegistered('No default provider registered');
        }

        $this->defaultProvider       = $this->factory->create($model->type, $model->row());
        $this->providers[$model->id] = $this->defaultProvider;

        return $this->defaultProvider;
    }

    private function getScope() : ?string
    {
        if ($this->scopeMatcher->isBackendRequest()) {
            return 'backend';
        }

        if ($this->scopeMatcher->isFrontendRequest()) {
            return 'frontend';
        }

        return null;
    }
}
