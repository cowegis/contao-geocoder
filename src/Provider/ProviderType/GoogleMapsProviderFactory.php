<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Geocoder\Provider\GoogleMaps\GoogleMaps;

/**
 * @psalm-type TGoogleMapsConfig = array{
 *     title: ?string,
 *     id: string,
 *     google_region?: ?string,
 *     google_api_key?: ?string
 * }
 */
final class GoogleMapsProviderFactory extends BaseHttpProviderTypeFactory
{
    protected const FEATURES = [Provider::FEATURE_ADDRESS, Provider::FEATURE_REVERSE];

    public function name(): string
    {
        return 'google_maps';
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param TGoogleMapsConfig $config
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function create(array $config, ProviderFactory $factory): Provider
    {
        $region = $config['google_region'] ?? null;
        $region = $region ?: null;
        $apiKey = $config['google_api_key'] ?? '';

        return $this->createDecorator(new GoogleMaps($this->httpClient, $region, $apiKey), $config);
    }
}
