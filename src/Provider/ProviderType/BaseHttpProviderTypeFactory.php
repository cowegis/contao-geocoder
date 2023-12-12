<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider\ProviderType;

use Psr\Http\Client\ClientInterface;

abstract class BaseHttpProviderTypeFactory extends BaseProviderTypeFactory
{
    public function __construct(protected readonly ClientInterface $httpClient)
    {
    }
}
