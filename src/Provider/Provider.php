<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocoderProvider;

interface Provider extends GeocoderProvider
{
    public const string FEATURE_ADDRESS = 'address';
    public const string FEATURE_REVERSE = 'reverse';

    public function title(): string;

    public function providerId(): string;

    public function type(): string;
}
