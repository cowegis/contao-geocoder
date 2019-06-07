<?php

declare(strict_types=1);

use Contao\System;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;

array_insert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'cowegis' => [
            'cowegis_geocoder' => [
                'tables' => ['tl_cowegis_geocoder_provider'],
            ],
        ],
    ]
);

/** @var RequestScopeMatcher $scopeMatcher */
$scopeMatcher = System::getContainer()->get('netzmacht.contao_toolkit.routing.scope_matcher');
if ($scopeMatcher->isBackendRequest()) {
    $GLOBALS['TL_CSS'][] = 'bundles/cowegiscontaogeocode/css/backend.css|static';
}
