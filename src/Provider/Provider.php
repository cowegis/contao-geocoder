<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocoderProvider;

interface Provider extends GeocoderProvider
{
    public function title() : string;

    public function id() : string;

    public function type() : string;
}
