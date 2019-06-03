<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Cowegis\ContaoGeocoder\Provider\ProviderTypeFactory;
use Http\Client\HttpClient;
use function in_array;

abstract class BaseProviderTypeFactory implements ProviderTypeFactory
{
    protected const FEATURES = [];

    /** @var HttpClient */
    protected $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function supports(string $feature) : bool
    {
        return in_array($feature, static::FEATURES, true);
    }
}
