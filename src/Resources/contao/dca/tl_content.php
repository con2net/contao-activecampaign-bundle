<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Resources/contao/dca/tl_content.php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Content Element: ActiveCampaign Form
 * Fügt ein neues Content Element hinzu für ActiveCampaign Integration
 */

// Palette für Content Element definieren
$GLOBALS['TL_DCA']['tl_content']['palettes']['activecampaign_form'] =
    '{type_legend},type,headline;'
    . '{form_legend},c2n_ac_form_id;'
    . '{activecampaign_legend},c2n_ac_list_id,c2n_ac_tags;'
    . '{delayed_legend},c2n_ac_delay_transfer;'
    . '{template_legend:hide},customTpl;'
    . '{invisible_legend:hide},invisible,start,stop';

// Subpalette für Delayed Transfer Optionen
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['c2n_ac_delay_transfer'] =
    'c2n_ac_auto_delete_days';

// Subpaletten-Feld als Selector registrieren!
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'c2n_ac_delay_transfer';

/**
 * Felder definieren
 */

// Formular-Auswahl
$GLOBALS['TL_DCA']['tl_content']['fields']['c2n_ac_form_id'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['c2n_ac_form_id'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['con2net.activecampaign.form_callback', 'getFormOptions'],
    'eval' => [
        'mandatory' => true,
        'includeBlankOption' => true,
        'chosen' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

// ActiveCampaign Listen-ID
$GLOBALS['TL_DCA']['tl_content']['fields']['c2n_ac_list_id'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['c2n_ac_list_id'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'maxlength' => 255,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(255) NOT NULL default ''"
];

// Tags
$GLOBALS['TL_DCA']['tl_content']['fields']['c2n_ac_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['c2n_ac_tags'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'maxlength' => 255,
        'tl_class' => 'w50',
        'helpwizard' => true
    ],
    'explanation' => 'activecampaign_tags',
    'sql' => "varchar(255) NOT NULL default ''"
];

// Delayed Transfer aktivieren
$GLOBALS['TL_DCA']['tl_content']['fields']['c2n_ac_delay_transfer'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['c2n_ac_delay_transfer'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

// Auto-Delete nach X Tagen
$GLOBALS['TL_DCA']['tl_content']['fields']['c2n_ac_auto_delete_days'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['c2n_ac_auto_delete_days'],
    'exclude' => true,
    'inputType' => 'text',
    'default' => 10,
    'eval' => [
        'mandatory' => true,
        'rgxp' => 'natural',
        'minval' => 1,
        'maxval' => 365,
        'tl_class' => 'w50'
    ],
    'sql' => "int(3) unsigned NOT NULL default '10'"
];