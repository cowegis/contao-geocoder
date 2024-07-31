<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Model;

use Contao\Model;

/** @property string $type */
final class ProviderModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_geocoder_provider';
}
