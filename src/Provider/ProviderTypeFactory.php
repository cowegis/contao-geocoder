<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

/** @psalm-import-type TProviderConfig from ProviderFactory */
interface ProviderTypeFactory
{
    public function name(): string;

    public function supports(string $feature): bool;

    /**
     * @param mixed[] $config
     * @psalm-param TProviderConfig $config
     */
    public function create(array $config, ProviderFactory $factory): Provider;
}
