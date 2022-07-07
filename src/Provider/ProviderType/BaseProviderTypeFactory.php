<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\GeocoderProviderDecoratorFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;

use function in_array;

abstract class BaseProviderTypeFactory implements ProviderTypeFactory
{
    use GeocoderProviderDecoratorFactory;

    /** @const list<string> */
    protected const FEATURES = [];

    public function supports(string $feature): bool
    {
        /** @psalm-suppress MixedArgument - No idea how to fix ist */
        return in_array($feature, self::FEATURES, true);
    }
}
