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

final class GeocoderAggregator implements IteratorAggregate, Geocoder
{
    /** @var ProviderFactory */
    private $factory;

    /** @var ProviderRepository */
    private $repository;

    /** @var GeocodeProvider */
    private $defaultProvider;

    /** @var RequestScopeMatcher */
    private $scopeMatcher;

    /** @var array<string,Geocoder> */
    private $providers;

    /** @var bool */
    private $dbLoaded = false;

    public function __construct(
        ProviderFactory $factory,
        ProviderRepository $repository,
        RequestScopeMatcher $scopeMatcher,
        array $providers = [],
        GeocodeProvider $defaultProvider = null
    ) {
        $this->factory         = $factory;
        $this->repository      = $repository;
        $this->scopeMatcher    = $scopeMatcher;
        $this->providers       = $providers;
        $this->defaultProvider = $defaultProvider;
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
        return 'cowegis_provider';
    }

    public function using(string $providerId) : Provider
    {
        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        if ($this->dbLoaded) {
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

    /** {@inheritDoc} */
    public function getIterator() : iterable
    {
        if ($this->dbLoaded) {
            return new ArrayIterator($this->providers);
        }

        $collection = $this->repository->findAll();

        foreach ($collection ?: [] as $model) {
            $this->providers[$model->id] = $this->factory->create($model->type, $model->row());
        }

        $this->dbLoaded = true;

        return new ArrayIterator($this->providers);
    }

    private function defaultProvider() : GeocodeProvider
    {
        if ($this->defaultProvider !== null) {
            return $this->defaultProvider;
        }

        $scope = $this->getScope();
        $model = $this->repository->findDefaultForScope($scope);
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
            return 'contao_backend';
        }

        if ($this->scopeMatcher->isFrontendRequest()) {
            return 'contao_frontend';
        }

        return null;
    }
}
