<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\DependencyInjection;

use Cowegis\ContaoGeocoder\Provider\ProviderType\GoogleMapsProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderType\NominatimProviderFactory;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Nominatim\Nominatim;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use function class_exists;

/**
 * @psalm-import-type TConfiguration from Configuration
 */
final class CowegisContaoGeocoderExtension extends Extension
{
    /** @param array|mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $config = $this->processConfiguration(new Configuration(), $configs);
        /** @psalm-var TConfiguration $config*/

        $container->setParameter('cowegis.contao_geocoder.config.default_provider', $config['default_provider']);
        $container->setParameter('cowegis.contao_geocoder.config.providers', $config['providers']);

        $loader->load('services.xml');
        $loader->load('listeners.xml');

        $this->checkNominatimSupport($container);
        $this->checkGoogleMapsSupport($container);
    }

    private function checkNominatimSupport(ContainerBuilder $container): void
    {
        if (class_exists(Nominatim::class)) {
            return;
        }

        $container->removeDefinition(NominatimProviderFactory::class);
    }

    private function checkGoogleMapsSupport(ContainerBuilder $container): void
    {
        if (class_exists(GoogleMaps::class)) {
            return;
        }

        $container->removeDefinition(GoogleMapsProviderFactory::class);
    }
}
