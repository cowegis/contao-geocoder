<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\GeocoderProviderDecoratorFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use function in_array;

abstract class BaseProviderTypeFactory implements ProviderTypeFactory
{
    use GeocoderProviderDecoratorFactory;

    protected const FEATURES = [];

    public function supports(string $feature) : bool
    {
        return in_array($feature, static::FEATURES, true);
    }
}
