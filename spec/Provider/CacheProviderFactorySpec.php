<?php

declare(strict_types=1);

namespace spec\Cowegis\ContaoGeocoder\Provider;

use Cowegis\ContaoGeocoder\Provider\CacheProviderFactory;
use Cowegis\ContaoGeocoder\Provider\GeocoderProviderDecorator;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use Geocoder\Provider\Cache\ProviderCache;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;

final class CacheProviderFactorySpec extends ObjectBehavior
{
    public function let(ProviderFactory $factory, CacheInterface $cache): void
    {
        $this->beConstructedWith($factory, $cache);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CacheProviderFactory::class);
    }

    public function it_delegates_register(ProviderFactory $factory, ProviderTypeFactory $typeFactory): void
    {
        $factory->register($typeFactory)->shouldBeCalledOnce();

        $this->register($typeFactory);
    }

    public function it_delegates_supports(ProviderFactory $factory): void
    {
        $factory->supports('type', Provider::FEATURE_ADDRESS)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $factory->supports('type', Provider::FEATURE_REVERSE)
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->supports('type', Provider::FEATURE_ADDRESS)->shouldReturn(true);
        $this->supports('type', Provider::FEATURE_REVERSE)->shouldReturn(false);
    }

    public function it_delegates_type_names(ProviderFactory $factory): void
    {
        $factory->typeNames()
            ->shouldBeCalledOnce()
            ->willReturn(['foo', 'bar']);

        $this->typeNames()->shouldReturn(['foo', 'bar']);
    }

    public function it_creates_cache_decorator_if_configured(ProviderFactory $factory, Provider $provider): void
    {
        $factory->create('foo', Argument::any())->willReturn($provider);
        $this->create('foo', ['id' => 'foo'])->shouldReturn($provider);

        $this->create('foo', ['id' => 'foo', 'title' => 'Foo', 'cache' => '1', 'cache_ttl' => 3600])
            ->shouldImplement(GeocoderProviderDecorator::class);

        $this->create('foo', ['id' => 'foo', 'title' => 'Foo', 'cache' => '1', 'cache_ttl' => 3600])
            ->provider()->shouldImplement(ProviderCache::class);
    }
}
