<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider as GeocodeProvider;

interface ProviderTypeFactory
{
    public function name() : string;

    public function supports(string $feature) : bool;

    /** @param mixed[] $config */
    public function create(array $config) : GeocodeProvider;
}
