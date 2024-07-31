<?php

declare(strict_types=1);

use Contao\ArrayUtil;

ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'cowegis' => [
            'cowegis_geocoder' => [
                'tables' => ['tl_cowegis_geocoder_provider'],
            ],
        ],
    ],
);
