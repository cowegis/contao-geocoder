<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Provider;

use Traversable;

/**
 * @extends Traversable<array{type: string, title: ?string, id: string}>
 */
interface ConfigProvider extends Traversable
{
}
