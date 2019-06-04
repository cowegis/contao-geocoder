<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFeature;
use Geocoder\Provider\GoogleMaps\GoogleMaps;

final class GoogleMapsProviderFactory extends BaseHttpProviderTypeFactory
{
    protected const FEATURES = [ProviderFeature::ADDRESS, ProviderFeature::REVERSE];

    public function name() : string
    {
        return 'google_maps';
    }

    /** {@inheritDoc} */
    public function create(array $config) : Provider
    {
        $region = $config['google_region'] ?? null;
        $region = $region ?: null;
        $apiKey = $config['google_api_key'] ?? '';

        return $this->createDecorator(new GoogleMaps($this->httpClient, $region, $apiKey), $config);
    }
}
