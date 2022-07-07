<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Cache\ProviderCache as GeocoderProviderCache;

final class ProviderCache extends GeocoderProviderCache
{
    /** {@inheritDocs} */
    protected function getCacheKey($query) : string
    {
        $cacheKey = parent::getCacheKey($query);
        if ($this->realProvider instanceof Provider) {
            return $this->realProvider->getName() . '.' .$cacheKey;
        }

        return $cacheKey;
    }
}
