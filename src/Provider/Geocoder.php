<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocodeProvider;
use Traversable;

interface Geocoder extends GeocodeProvider, Traversable
{
    public function using(string $providerId) : Provider;
}
