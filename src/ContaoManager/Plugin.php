<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Cowegis\ContaoGeocoder\CowegisContaoGeocodeBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /** @return ConfigInterface[] */
    public function getBundles(ParserInterface $parser) : array
    {
        return [BundleConfig::create(CowegisContaoGeocodeBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel) : ?RouteCollection
    {
        $loader = $resolver->resolve(__DIR__ . '/../Resources/config/routing.xml');
        if (! $loader) {
            return null;
        }

        return $loader->load(__DIR__ . '/../Resources/config/routing.xml');
    }
}
