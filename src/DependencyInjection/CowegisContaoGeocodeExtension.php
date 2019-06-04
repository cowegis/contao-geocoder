<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class CowegisContaoGeocodeExtension extends Extension
{
    /** @param mixed[][] $configs */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('cowegis.contao_geocoder.config.default_provider', $config['default_provider']);
        $container->setParameter('cowegis.contao_geocoder.config.providers', $config['providers']);

        $loader->load('services.xml');
        $loader->load('listeners.xml');
    }
}
