<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder;

use Cowegis\ContaoGeocoder\DependencyInjection\Compiler\RegisterProviderTypeFactoriesPass;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CowegisContaoGeocoderBundle extends Bundle
{
    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterProviderTypeFactoriesPass());
    }
}
