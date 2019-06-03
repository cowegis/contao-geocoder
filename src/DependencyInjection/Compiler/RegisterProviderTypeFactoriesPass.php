<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\DependencyInjection\Compiler;

use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterProviderTypeFactoriesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container) : void
    {
        if (! $container->hasDefinition(ProviderFactory::class)) {
            return;
        }

        $definition = $container->getDefinition(ProviderFactory::class);
        $references = $this->findAndSortTaggedServices(ProviderTypeFactory::class, $container);

        foreach ($references as $reference) {
            $definition->addMethodCall('register', [$reference]);
        }
    }
}
