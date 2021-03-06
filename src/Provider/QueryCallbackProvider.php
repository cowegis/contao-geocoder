<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Collection;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class QueryCallbackProvider implements Provider
{
    /** @var Provider */
    private $provider;

    /** @var null|callable(GeocodeQuery): GeocodeQuery */
    private $geocodeQueryCallback;

    /** @var null|callable(ReverseQuery): ReverseQuery */
    private $reverseQueryCallback;

    /**
     * @param null|callable(GeocodeQuery): GeocodeQuery $geocodeQueryCallback
     * @param null|callable(ReverseQuery): ReverseQuery $reverseQueryCallback
     */
    public function __construct(
        Provider $provider,
        ?callable $geocodeQueryCallback = null,
        ?callable $reverseQueryCallback = null
    ) {
        $this->provider             = $provider;
        $this->geocodeQueryCallback = $geocodeQueryCallback;
        $this->reverseQueryCallback = $reverseQueryCallback;
    }

    public function title() : string
    {
        return $this->provider->title();
    }

    public function providerId() : string
    {
        return $this->provider->providerId();
    }

    public function type() : string
    {
        return $this->provider->type();
    }

    public function getName() : string
    {
        return $this->provider->getName();
    }

    public function geocodeQuery(GeocodeQuery $query) : Collection
    {
        if ($this->geocodeQueryCallback) {
            $callback = $this->geocodeQueryCallback;
            $query    = $callback($query);
        }

        return $this->provider->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query) : Collection
    {
        if ($this->reverseQueryCallback) {
            $callback = $this->reverseQueryCallback;
            $query    = $callback($query);
        }

        return $this->provider->reverseQuery($query);
    }
}
