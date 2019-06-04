<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\DependencyInjection;

use function array_keys;
use Assert\Assert;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $builder  = new TreeBuilder();
        $rootNode = $builder->root('cowegis_contao_geocoder');

        $rootNode
            ->children()
                ->scalarNode('default_provider')
                    ->defaultNull()
                ->end()
                ->arrayNode('providers')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')
                            ->end()
                            ->arrayNode('config')
                                ->scalarPrototype()
                                ->end()
                                ->children()
                                    ->scalarNode('title')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->beforeNormalization()
                            ->ifTrue(
                                static function ($value) {
                                    return $value['type'] === 'google_maps';
                                }
                            )->then(
                                static function ($value) {
                                        Assert::that($value['config'])->keyExists('google_api_key');
                                        Assert::that($value['config']['google_api_key'])->string();

                                        return $value;
                                }
                            )
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->always(static function ($value) {
                        foreach (array_keys($value['providers']) as $providerId) {
                            $value['providers'][$providerId]['config']['id'] = $providerId;
                        }

                        return $value;
                    })
                ->end()
            ->end();

        return $builder;
    }
}
