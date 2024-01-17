<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

final class GeocoderFactory
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly ProviderFactory $providerFactory,
    ) {
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
