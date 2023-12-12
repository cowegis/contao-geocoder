<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Contao\StringUtil;
use Cowegis\ContaoGeocoder\Model\ProviderModel;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Geocoder\Provider\Chain\Chain;

use function array_map;
use function assert;

/**
 * @psalm-import-type TProviderConfig from ProviderFactory
 * @psalm-type TChainConfig = array{
 *     type: string,
 *     title: ?string,
 *     id: string,
 *     chain_providers?: string,
 *     cache:int|numeric-string|bool,
 *     cache_ttl: int|numeric-string
 * }
 */
final class ChainProviderFactory extends BaseProviderTypeFactory
{
    protected const FEATURES = [Provider::FEATURE_ADDRESS, Provider::FEATURE_REVERSE];

    public function __construct(
        private readonly ProviderFactory $factory,
        private readonly ProviderRepository $repository,
    ) {
    }

    public function name(): string
    {
        return 'chain';
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param TChainConfig $config
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function create(array $config): Provider
    {
        $chain       = new Chain();
        $providerIds = array_map(
            'intval',
            (array) StringUtil::deserialize($config['chain_providers'] ?? '', true),
        );

        $providers = $this->repository->findByIds($providerIds);

        foreach ($providers ?: [] as $provider) {
            assert($provider instanceof ProviderModel);
            /** @psalm-var TProviderConfig $providerConfig */
            $providerConfig = $provider->row();
            $chain->add($this->factory->create($provider->type, $providerConfig));
        }

        return $this->createDecorator($chain, $config);
    }
}
