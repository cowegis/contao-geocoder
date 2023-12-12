<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Traversable;

/**
 * @psalm-type TProviderConfig = array{
 *     type: string,
 *     title: ?string,
 *     id: string,
 *     cache:int|numeric-string|bool,
 *     cache_ttl: int|numeric-string,
 *     ...
 * }
 * @extends Traversable<TProviderConfig>
 */
interface ConfigProvider extends Traversable
{
}
