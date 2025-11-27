<?php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Contao\Database;

/**
 * Callback-Klasse für die Formular-Auswahl im Content Element
 *
 * Lädt alle verfügbaren Formulare aus der Datenbank
 */
class FormOptionsCallback
{
    /**
     * Gibt alle verfügbaren Formulare als Options-Array zurück
     *
     * @return array Assoziatives Array mit Form-ID als Key und Titel als Value
     */
    public function getFormOptions(): array
    {
        $options = [];

        // Alle Formulare aus der Datenbank holen
        $result = Database::getInstance()
            ->prepare("SELECT id, title FROM tl_form ORDER BY title")
            ->execute();

        while ($result->next()) {
            // ID => Titel für das Dropdown
            $options[$result->id] = $result->title;
        }

        return $options;
    }
}