<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Resources/contao/languages/de/tl_c2n_activecampaign.php

declare(strict_types=1);

/**
 * Sprachdatei für tl_c2n_activecampaign (Deutsch)
 */

// Legends
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['transfer_legend'] = 'Transfer-Informationen';
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['data_legend'] = 'Formulardaten';
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['date_legend'] = 'Zeitstempel';

// Fields
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['token'] = ['Token', 'Eindeutiger Token für den Transfer-Link'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['form_id'] = ['Formular-ID', 'ID des Contao-Formulars'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['email'] = ['E-Mail', 'E-Mail-Adresse des Kontakts'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['status'] = ['Status', 'Aktueller Status der Übertragung'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['created_at'] = ['Erstellt am', 'Zeitpunkt der Formular-Übermittlung'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['processed_at'] = ['Verarbeitet am', 'Zeitpunkt der ActiveCampaign-Übertragung'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['auto_delete_at'] = ['Auto-Löschung am', 'Zeitpunkt der automatischen Löschung'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['json_data'] = ['Formulardaten (JSON)', 'Alle übermittelten Formulardaten'];

// Status Options
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['status_options'] = [
    'pending' => 'Ausstehend',
    'processed' => 'Verarbeitet',
    'expired' => 'Abgelaufen',
    'deleted' => 'Gelöscht'
];

// Operations
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['show'] = ['Details anzeigen', 'Details des Eintrags ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_c2n_activecampaign']['delete'] = ['Löschen', 'Eintrag ID %s löschen'];

