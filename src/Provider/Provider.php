<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocoderProvider;

interface Provider extends GeocoderProvider
{
    public const FEATURE_ADDRESS = 'address';
    public const FEATURE_REVERSE = 'reverse';

    public function title() : string;

    public function providerId() : string;

    public function type() : string;
}
