<?php

declare(strict_types=1);

use Contao\DC_Table;
use Cowegis\ContaoGeocoder\EventListener\Dca\ProviderDcaListener;

$GLOBALS['TL_DCA']['tl_cowegis_geocoder_provider'] = [
    'config'          => [
        'dataContainer'     => DC_Table::class,
        'enableVersioning'  => true,
        'markAsCopy'        => 'headline',
        'onsubmit_callback' => [[ProviderDcaListener::class, 'setDefault']],
        'sql'               => [
            'keys' => [
                'id'        => 'primary',
                'isDefault' => 'index',
            ],
        ],
    ],
    'list'            => [
        'sorting'           => [
            'fields' => ['title'],
            'mode'   => 1,
            'flag'   => 1,
        ],
        'label'             => [
            'fields'         => ['title', 'type'],
            'label_callback' => [ProviderDcaListener::class, 'formatLabel'],
        ],
        'global_operations' => [
            'all'        => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'copy'   => [
                'href'       => 'act=copy',
                'icon'       => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => sprintf(
                    'onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()"',
                    ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? ''),
                ),
            ],
            'show'   => [
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],
    'palettes'        => [
        '__selector__' => ['type'],
    ],
    'metapalettes'    => [
        'default'                     => [
            'title'  => ['title', 'type', 'isDefault'],
            'config' => [],
            'cache'  => ['cache'],
        ],
        'nominatim extends default'   => [
            'config' => ['nominatim_root_url', 'nominatim_country_codes'],
        ],
        'google_maps extends default' => [
            'config' => ['google_api_key', 'google_region'],
        ],
        'chain extends default'       => [
            'config' => ['chain_providers'],
        ],
    ],
    'metasubpalettes' => [
        'cache' => ['cache_ttl'],
    ],
    'fields'          => [
        'id'                      => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'pid'                     => ['sql' => 'int(10) unsigned NOT NULL default \'0\''],
        'tstamp'                  => ['sql' => 'int(10) unsigned NOT NULL default \'0\''],
        'title'                   => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'type'                    => [
            'exclude'          => true,
            'filter'           => true,
            'inputType'        => 'select',
            'options_callback' => [ProviderDcaListener::class, 'typeOptions'],
            'reference'        => &$GLOBALS['TL_LANG']['tl_cowegis_geocoder_provider']['types'],
            'eval'             => [
                'mandatory'          => true,
                'helpwizard'         => true,
                'chosen'             => true,
                'submitOnChange'     => true,
                'includeBlankOption' => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => 'varchar(64) NOT NULL default \'\'',
        ],
        'isDefault'               => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => 'char(1) NOT NULL default \'\'',
        ],
        'cache'                   => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
            'sql'       => 'char(1) NOT NULL default \'\'',
        ],
        'cache_ttl'               => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural'],
            'sql'       => 'int(10) UNSIGNED NOT NULL default \'0\'',
        ],
        'chain_providers'         => [
            'exclude'          => true,
            'filter'           => true,
            'inputType'        => 'checkboxWizard',
            'options_callback' => [ProviderDcaListener::class, 'providerOptions'],
            'eval'             => [
                'mandatory' => true,
                'tl_class'  => 'clr',
                'multiple'  => true,
            ],
            'sql'              => 'blob NULL',
        ],
        'nominatim_root_url'      => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'clr long'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'nominatim_country_codes' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'google_api_key'          => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'mandatory' => true, 'tl_class' => 'clr long'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'google_region'           => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
    ],
];
