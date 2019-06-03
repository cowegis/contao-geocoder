<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\ProviderFeature;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Provider\Provider;

final class NominatimProviderFactory extends BaseProviderTypeFactory
{
    protected const FEATURES = [ProviderFeature::REVERSE, ProviderFeature::ADDRESS];

    public function name() : string
    {
        return 'nominatim';
    }

    /** {@inheritDoc} */
    public function create(array $config) : Provider
    {
        $rootUrl = $config['nominatim_root_url'] ?? null;
        $rootUrl = $rootUrl ?: 'http://nominatim.openstreetmap.org';

        return new Nominatim($this->httpClient, $rootUrl, 'Cowegis Geocoder');
    }
}
