<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Cowegis\ContaoGeocoder\CowegisContaoGeocoderBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

use function assert;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * @return ConfigInterface[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(CowegisContaoGeocoderBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $loader = $resolver->resolve(__DIR__ . '/../Resources/config/routing.xml');
        if ($loader === false) {
            return null;
        }

        $collection = $loader->load(__DIR__ . '/../Resources/config/routing.xml');
        assert($collection instanceof RouteCollection || $collection === null);

        return $collection;
    }
}
