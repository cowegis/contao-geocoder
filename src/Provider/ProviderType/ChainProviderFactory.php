<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Contao\StringUtil;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderFeature;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use Geocoder\Provider\Chain\Chain;
use Geocoder\Provider\Provider;
use function in_array;

final class ChainProviderFactory implements ProviderTypeFactory
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

    public function supports(string $feature) : bool
    {
        return in_array($feature, static::FEATURES, true);
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

        return $chain;
    }
}
