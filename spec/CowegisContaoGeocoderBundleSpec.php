<?php

namespace spec\Cowegis\ContaoGeocoder;

use Cowegis\ContaoGeocoder\CowegisContaoGeocoderBundle;
use Cowegis\ContaoGeocoder\DependencyInjection\Compiler\RegisterProviderTypeFactoriesPass;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CowegisContaoGeocoderBundleSpec extends ObjectBehavior
{
    public function it_is_initializable() : void
    {
        $this->shouldHaveType(CowegisContaoGeocoderBundle::class);
    }

    public function it_is_a_bundle() : void
    {
        $this->shouldBeAnInstanceOf(Bundle::class);
    }

    public function it_registers_compiler_passes(ContainerBuilder $container) : void
    {
        $container->addCompilerPass(Argument::type(RegisterProviderTypeFactoriesPass::class))->shouldBeCalledOnce();

        $this->build($container);
    }
}
