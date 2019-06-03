<?php

declare(strict_types=1);

$GLOBALS['BE_MOD']['cowegis'] = [
    'cowegis_geocode' => [
        'tables' => ['tl_cowegis_geocoder_provider'],
    ],
];

if (TL_MODE === 'BE') {
    $GLOBALS['TL_CSS'][] = 'bundles/cowegiscontaogeocode/css/backend.css|static';
}
