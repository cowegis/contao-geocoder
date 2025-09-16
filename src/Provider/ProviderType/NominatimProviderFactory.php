<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\QueryCallbackProvider;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Override;

/**
 * @psalm-type TNominatimConfig = array{
 *   title: ?string,
 *   id: string,
 *   nominatim_root_url?: ?string,
 *   nominatim_country_codes?: ?string
 * }
 */
final class NominatimProviderFactory extends BaseHttpProviderTypeFactory
{
    protected const array FEATURES = [Provider::FEATURE_REVERSE, Provider::FEATURE_ADDRESS];

    #[Override]
    public function name(): string
    {
        return 'nominatim';
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param TNominatimConfig $config
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    #[Override]
    public function create(array $config, ProviderFactory $factory): Provider
    {
        $rootUrl = $config['nominatim_root_url'] ?? null;
        if ($rootUrl === null || $rootUrl === '') {
            $rootUrl = 'https://nominatim.openstreetmap.org';
        }

        $countryCodes = $config['nominatim_country_codes'] ?? null;
        $provider     = $this->createDecorator(new Nominatim($this->httpClient, $rootUrl, 'Cowegis Geocoder'), $config);

        if ($countryCodes === null) {
            return $provider;
        }

        return new QueryCallbackProvider(
            $provider,
            static function (GeocodeQuery $geocodeQuery) use ($countryCodes): GeocodeQuery {
                if ($geocodeQuery->getData('countrycodes') === null) {
                    return $geocodeQuery->withData('countrycodes', $countryCodes);
                }

                return $geocodeQuery;
            },
        );
    }
}
