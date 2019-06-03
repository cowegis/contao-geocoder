<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocodeProvider;

interface Provider extends GeocodeProvider
{
    public function using(int $providerId) : GeocodeProvider;
}
