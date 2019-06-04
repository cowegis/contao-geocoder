<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Contao\StringUtil;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderFeature;
use Geocoder\Provider\Chain\Chain;

final class ChainProviderFactory extends BaseProviderTypeFactory
{
    protected const FEATURES = [ProviderFeature::ADDRESS, ProviderFeature::REVERSE];

    /** @var ProviderFactory */
    private $factory;

    /** @var ProviderRepository */
    private $repository;

    public function __construct(ProviderFactory $providerFactory, ProviderRepository $repository)
    {
        $this->factory    = $providerFactory;
        $this->repository = $repository;
    }

    public function name() : string
    {
        return 'chain';
    }

    /** {@inheritDoc} */
    public function create(array $config) : Provider
    {
        $chain       = new Chain();
        $providerIds = StringUtil::deserialize($config['chain_providers'] ?? '', true);
        $providers   = $this->repository->findByIds($providerIds);

        foreach ($providers ?: [] as $provider) {
            $chain->add($this->factory->create($provider->type, $provider->row()));
        }

        return $this->createDecorator($chain, $config);
    }
}
