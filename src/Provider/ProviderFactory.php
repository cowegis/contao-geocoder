<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Geocoder\Provider\Provider;

interface ProviderFactory
{
    public function register(ProviderTypeFactory $factory) : void;

    public function supports(string $type, string $feature) : bool;

    /** @param mixed[] $config */
    public function create(string $type, array $config) : Provider;

    /** @return string[] */
    public function typeNames() : array;
}
