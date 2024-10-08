<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Collection;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class QueryCallbackProvider implements Provider
{
    /** @var callable(GeocodeQuery): GeocodeQuery|null */
    private $geocodeQueryCallback;

    /** @var callable(ReverseQuery): ReverseQuery|null */
    private $reverseQueryCallback;

    /**
     * @param callable(GeocodeQuery): GeocodeQuery|null $geocodeQueryCallback
     * @param callable(ReverseQuery): ReverseQuery|null $reverseQueryCallback
     */
    public function __construct(
        private readonly Provider $provider,
        callable|null $geocodeQueryCallback = null,
        callable|null $reverseQueryCallback = null,
    ) {
        $this->geocodeQueryCallback = $geocodeQueryCallback;
        $this->reverseQueryCallback = $reverseQueryCallback;
    }

    public function title(): string
    {
        return $this->provider->title();
    }

    public function providerId(): string
    {
        return $this->provider->providerId();
    }

    public function type(): string
    {
        return $this->provider->type();
    }

    public function getName(): string
    {
        return $this->provider->getName();
    }

    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        if ($this->geocodeQueryCallback !== null) {
            $callback = $this->geocodeQueryCallback;
            $query    = $callback($query);
        }

        return $this->provider->geocodeQuery($query);
    }

    public function reverseQuery(ReverseQuery $query): Collection
    {
        if ($this->reverseQueryCallback !== null) {
            $callback = $this->reverseQueryCallback;
            $query    = $callback($query);
        }

        return $this->provider->reverseQuery($query);
    }
}
