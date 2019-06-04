<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Assert\Assert;
use Geocoder\Provider\Provider as GeocodeProvider;

trait GeocoderProviderDecoratorFactory
{
    /** @param mixed[] $config */
    protected function createDecorator(GeocodeProvider $provider, array $config) : Provider
    {
        Assert::that($config)
            ->keyExists('id')
            ->keyExists('title');

        Assert::that($config['id'])->string();
        Assert::that($config['title'])->nullOr()->string();

        return new GeocoderProviderDecorator($provider, $config['id'], $config['title']);
    }
}
