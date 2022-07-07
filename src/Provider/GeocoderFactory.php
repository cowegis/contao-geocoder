<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

final class GeocoderFactory
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var ProviderFactory */
    private $providerFactory;

    public function __construct(ConfigProvider $configProvider, ProviderFactory $providerFactory)
    {
        $this->configProvider  = $configProvider;
        $this->providerFactory = $providerFactory;
    }

    public function __invoke(): Geocoder
    {
        $geocoder = new Geocoder();

        foreach ($this->configProvider as $config) {
            $provider = $this->providerFactory->create($config['type'], $config);
            $geocoder->register($provider);
        }

        return $geocoder;
    }
}
