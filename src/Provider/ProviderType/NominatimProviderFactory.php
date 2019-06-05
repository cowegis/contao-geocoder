<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Provider\Nominatim\Nominatim;

final class NominatimProviderFactory extends BaseHttpProviderTypeFactory
{
    protected const FEATURES = [Provider::FEATURE_REVERSE, Provider::FEATURE_ADDRESS];

    public function name() : string
    {
        return 'nominatim';
    }

    /** {@inheritDoc} */
    public function create(array $config) : Provider
    {
        $rootUrl = $config['nominatim_root_url'] ?? null;
        $rootUrl = $rootUrl ?: 'https://nominatim.openstreetmap.org';

        return $this->createDecorator(new Nominatim($this->httpClient, $rootUrl, 'Cowegis Geocoder'), $config);
    }
}
