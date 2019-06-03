<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Model\ProviderModel;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Provider\Provider as GeocodeProvider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;

final class ProviderAggregator implements Provider
{
    /** @var ProviderFactory */
    private $factory;

    /** @var ProviderRepository */
    private $repository;

    /** @var Provider */
    private $defaultProvider;

    /** @var RequestScopeMatcher */
    private $scopeMatcher;

    /** @var array<int,Provider> */
    private $providers = [];

    public function __construct(
        ProviderFactory $factory,
        ProviderRepository $repository,
        RequestScopeMatcher $scopeMatcher
    ) {
        $this->factory      = $factory;
        $this->repository   = $repository;
        $this->scopeMatcher = $scopeMatcher;
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

    public function using(int $providerId) : GeocodeProvider
    {
        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        /** @var ProviderModel|null $model */
        $model = $this->repository->find($providerId);
        if ($model === null) {
            throw ProviderNotRegistered::create((string) $providerId);
        }

        $this->providers[$providerId] = $this->factory->create($model->type, $model->row());

        return $this->providers[$providerId];
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
