<?php

namespace spec\Cowegis\ContaoGeocoder\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Cowegis\ContaoGeocoder\ContaoManager\Plugin;
use Cowegis\ContaoGeocoder\CowegisContaoGeocoderBundle;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class PluginSpec extends ObjectBehavior
{
    public function it_is_initializable() : void
    {
        $this->shouldHaveType(Plugin::class);
    }

    public function it_provides_bundle_configs(ParserInterface $parser) : void
    {
        $this->shouldImplement(BundlePluginInterface::class);

        $this->getBundles($parser)->shouldHaveCount(1);

        $this->getBundles($parser)[0]->getName()->shouldReturn(CowegisContaoGeocoderBundle::class);
        $this->getBundles($parser)[0]->getLoadAfter()->shouldContain(ContaoCoreBundle::class);
    }

    public function it_provides_routing_collection(
        LoaderResolverInterface $resolver,
        KernelInterface $kernel,
        LoaderInterface $loader,
        RouteCollection $routeCollection
    ) : void {
        $this->shouldImplement(RoutingPluginInterface::class);

        $resolver->resolve(Argument::containingString('Resources/config/routing.xml'))
            ->shouldBeCalledOnce()
            ->willReturn($loader);

        $loader->load(Argument::containingString('Resources/config/routing.xml'))
            ->shouldBeCalledOnce()
            ->willReturn($routeCollection);

        $this->getRouteCollection($resolver, $kernel)->shouldReturn($routeCollection);
    }
}
