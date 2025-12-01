<?php
// File: src/EventListener/CleanupFormDataListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;

/**
 * Cleanup Listener (Priority 0)
 *
 * Entfernt technische ActiveCampaign-Felder aus submittedData,
 * damit sie NICHT in ##raw_data## und E-Mails landen.
 *
 * WICHTIG: Läuft NACH PrepareTransferTokenListener (Priority 50)
 * aber VOR der E-Mail-Erstellung!
 */
#[AsHook('prepareFormData', priority: 0)]
class CleanupFormDataListener
{
    /**
     * Hook: prepareFormData (Priority 0)
     *
     * Entfernt interne Felder aus $submittedData und sichert sie in GLOBALS
     * für den späteren FormSubmitListener (processFormData Hook)
     */
    public function __invoke(
        array &$submittedData,
        array &$labels,
        array $fields,
        Form $form
    ): void {
        $this->saveToGlobalsForLaterUse($submittedData);
        $this->removeInternalFields($submittedData);
    }

    /**
     * Sichert Token-Felder in GLOBALS für FormSubmitListener
     *
     * Der FormSubmitListener läuft später (processFormData Hook)
     * und braucht diese Werte noch, auch wenn sie aus $submittedData
     * entfernt wurden.
     */
    private function saveToGlobalsForLaterUse(array $submittedData): void
    {
        // Token sichern
        if (isset($submittedData['_ac_transfer_token'])) {
            $GLOBALS['ACTIVECAMPAIGN_TRANSFER_TOKEN'] = $submittedData['_ac_transfer_token'];
        }

        // Transfer-URL sichern
        if (isset($submittedData['_ac_transfer_url'])) {
            $GLOBALS['ACTIVECAMPAIGN_TRANSFER_URL'] = $submittedData['_ac_transfer_url'];
        }

        // Config sichern (hat bereits Fallback im FormSubmitListener, aber schadet nicht)
        if (isset($submittedData['_ac_config'])) {
            $GLOBALS['ACTIVECAMPAIGN_CONFIG'] = $submittedData['_ac_config'];
        }
    }

    /**
     * Entfernt alle internen ActiveCampaign-Felder aus submittedData
     *
     * Diese Felder sollen NICHT in ##raw_data## erscheinen!
     */
    private function removeInternalFields(array &$submittedData): void
    {
        // Interne Token-Felder (mit Unterstrich)
        unset($submittedData['_ac_transfer_token']);
        unset($submittedData['_ac_transfer_url']);
        unset($submittedData['_ac_config']);

        // Transfer-Link für E-Mail (ohne Unterstrich, wird in NC 1.x zu ##form_activecampaign_transfer_link##)
        // WICHTIG: Wird NICHT entfernt! Soll ja in der E-Mail bleiben.
        // unset($submittedData['activecampaign_transfer_link']); // <- auskommentiert!
    }
}
