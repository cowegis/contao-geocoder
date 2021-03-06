<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\DependencyInjection;

use Assert\Assert;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function array_keys;

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
                        ->scalarPrototype()
                        ->end()
                        ->children()
                            ->scalarNode('type')
                            ->end()
                            ->scalarNode('title')
                            ->end()
                        ->end()
                        ->beforeNormalization()
                            ->ifTrue(
                                static function ($value) {
                                    return $value['type'] === 'google_maps';
                                }
                            )->then(
                                static function ($value) {
                                        Assert::that($value)->keyExists('google_api_key');
                                        Assert::that($value['google_api_key'])->string();

                                        return $value;
                                }
                            )
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->always(static function ($value) {
                        if (!isset($value['providers'])) {
                            return $value;
                        }
                        foreach (array_keys($value['providers']) as $providerId) {
                            $value['providers'][$providerId]['id'] = $providerId;
                        }

                        return $value;
                    })
                ->end()
            ->end();

        return $builder;
    }
}
