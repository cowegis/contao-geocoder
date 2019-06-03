<?php

declare(strict_types=1);

array_insert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'cowegis' => [
            'cowegis_geocoder' => [
                'tables' => ['tl_cowegis_geocoder_provider'],
            ],
        ]
    ]
);

if (TL_MODE === 'BE') {
    $GLOBALS['TL_CSS'][] = 'bundles/cowegiscontaogeocode/css/backend.css|static';
}
