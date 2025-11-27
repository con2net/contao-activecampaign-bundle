<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Resources/contao/languages/de/tl_content.php

declare(strict_types=1);

/**
 * Sprachdatei für tl_content (Deutsch)
 */

// Bezeichnung
$GLOBALS['TL_LANG']['CTE']['activecampaign_form'] = [
    'ActiveCampaign Formular',
    'Formular mit ActiveCampaign Integration und optionalem Delayed Transfer'
];

// Legenden
$GLOBALS['TL_LANG']['tl_content']['form_legend'] = 'Formular-Auswahl';
$GLOBALS['TL_LANG']['tl_content']['activecampaign_legend'] = 'ActiveCampaign Einstellungen';
$GLOBALS['TL_LANG']['tl_content']['delayed_legend'] = 'Manuelle Übertragung (Delayed Transfer)';

// Feld-Labels
$GLOBALS['TL_LANG']['tl_content']['c2n_ac_form_id'] = [
    'Formular',
    'Wähle das Formular aus, das angezeigt werden soll.'
];

$GLOBALS['TL_LANG']['tl_content']['c2n_ac_list_id'] = [
    'ActiveCampaign Listen-ID',
    'Die ID der Liste in ActiveCampaign, zu der die Kontakte hinzugefügt werden sollen. <a href="/activecampaign/fields" target="_blank" style="font-weight:bold; color:#0066cc; text-decoration:underline;">» Custom Field IDs anzeigen</a>'
];

$GLOBALS['TL_LANG']['tl_content']['c2n_ac_tags'] = [
    'Tags',
    'Optional: Komma-getrennte Liste von Tags, die dem Kontakt in ActiveCampaign zugewiesen werden (z.B. "Website-Kontakt, Lead-2025").'
];

$GLOBALS['TL_LANG']['tl_content']['c2n_ac_delay_transfer'] = [
    'Manuelle Übertragung (Delayed Transfer)',
    'Wenn aktiviert, werden die Daten NICHT sofort zu ActiveCampaign übertragen. Stattdessen erhältst du eine E-Mail mit einem Link zur manuellen Übertragung.'
];

$GLOBALS['TL_LANG']['tl_content']['c2n_ac_auto_delete_days'] = [
    'Auto-Löschung nach Tagen',
    'Anzahl der Tage, nach denen nicht übertragene Daten automatisch gelöscht werden (1-365, empfohlen: 10).'
];

// Wizard-Button
$GLOBALS['TL_LANG']['tl_content']['c2n_ac_fields_wizard'] = [
    'Felder anzeigen',
    'Zeigt alle verfügbaren ActiveCampaign Felder und ihre IDs an'
];