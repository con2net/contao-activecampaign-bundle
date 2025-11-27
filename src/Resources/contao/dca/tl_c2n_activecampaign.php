<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Resources/contao/dca/tl_c2n_activecampaign.php

declare(strict_types=1);

/**
 * Table tl_c2n_activecampaign
 * Speichert Formulardaten für verzögerte ActiveCampaign-Übertragung
 */

$GLOBALS['TL_DCA']['tl_c2n_activecampaign'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => '',
        'ctable' => [],
        'switchToEdit' => false,
        'enableVersioning' => false,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'token' => 'unique',
                'form_id' => 'index',
                'status' => 'index',
                'auto_delete_at' => 'index',
                'created_at' => 'index'
            ]
        ]
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['created_at DESC'],
            'flag' => 6,
            'panelLayout' => 'filter;search,limit'
        ],
        'label' => [
            'fields' => ['email', 'form_id', 'status', 'created_at'],
            'format' => '%s (Form: %s) - Status: %s - %s',
            'label_callback' => ['tl_c2n_activecampaign', 'formatLabel']
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
        'default' => '{transfer_legend},token,email,form_id,status;{data_legend},json_data;{date_legend},created_at,processed_at,auto_delete_at'
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'token' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['token'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'readonly' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'form_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['form_id'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['email'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'readonly' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['status'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['pending', 'processed', 'expired', 'deleted'],
            'reference' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['status_options'],
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(20) NOT NULL default 'pending'"
        ],
        'created_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['created_at'],
            'exclude' => true,
            'flag' => 6,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'readonly' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'processed_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['processed_at'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'readonly' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NULL"
        ],
        'auto_delete_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['auto_delete_at'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'readonly' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'json_data' => [
            'label' => &$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['json_data'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['readonly' => true, 'style' => 'height:200px', 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ]
    ]
];

/**
 * Callback-Klasse für Tabelle tl_c2n_activecampaign
 */
class tl_c2n_activecampaign
{
    /**
     * Formatiert das Label in der Übersicht
     */
    public function formatLabel($row, $label, $dc, $args)
    {
        // E-Mail
        $args[0] = '<strong>' . $args[0] . '</strong>';

        // Status mit Farbe
        $statusColors = [
            'pending' => '#ff8c00',     // Orange
            'processed' => '#28a745',   // Grün
            'expired' => '#dc3545',     // Rot
            'deleted' => '#6c757d'      // Grau
        ];
        $color = $statusColors[$row['status']] ?? '#000';
        $args[2] = sprintf('<span style="color:%s;font-weight:bold;">%s</span>', $color, $args[2]);

        // Datum formatieren
        $args[3] = date('d.m.Y H:i', $row['created_at']);

        return $args;
    }
}
