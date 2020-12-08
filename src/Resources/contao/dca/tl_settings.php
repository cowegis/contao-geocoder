<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][]  = 'cowegis_geocoder_referrer_check';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default']        .= ';{cowegis_geocoder}'
    . ',cowegis_geocoder_api_key,cowegis_geocoder_referrer_check';

$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['cowegis_geocoder_referrer_check'] = 'cowegis_geocoder_referrer_domains';

$GLOBALS['TL_DCA']['tl_settings']['fields']['cowegis_geocoder_referrer_check'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['cowegis_geocoder_referrer_check'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12', 'submitOnChange' => true],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['cowegis_geocoder_referrer_domains'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['cowegis_geocoder_referrer_domains'],
    'exclude'   => true,
    'inputType' => 'listWizard',
    'eval'      => ['tl_class' => 'clr'],
];


$GLOBALS['TL_DCA']['tl_settings']['fields']['cowegis_geocoder_api_key'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['cowegis_geocoder_api_key'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'long'],
];
