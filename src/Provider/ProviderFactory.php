<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

/**
 * @psalm-type TProviderConfig = array{type: string, title: ?string, id: string}
 */
interface ProviderFactory
{
    public function register(ProviderTypeFactory $factory) : void;

    public function supports(string $type, string $feature) : bool;

    /**
     * @param mixed[] $config
     * @psalm-param TProviderConfig $config
     */
    public function create(string $type, array $config) : Provider;

    /** @return list<string> */
    public function typeNames() : array;
}
