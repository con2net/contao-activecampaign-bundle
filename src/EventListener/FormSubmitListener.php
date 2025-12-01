<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/EventListener/FormSubmitListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Con2net\ContaoActiveCampaignBundle\Service\ActiveCampaignService;
use Con2net\ContaoActiveCampaignBundle\Service\DelayedTransferStorage;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\System;
use Psr\Log\LoggerInterface;

/**
 * Hook Listener für Formular-Submits
 * Überträgt Formulardaten an ActiveCampaign ODER speichert sie für Delayed Transfer
 *
 * WICHTIG: Bei Delayed Transfer wird KEIN SPAM-Check gemacht!
 * Der Redakteur entscheidet manuell ob er die Daten überträgt.
 */
class FormSubmitListener
{
    private ActiveCampaignService $activeCampaignService;
    private DelayedTransferStorage $storage;
    private LoggerInterface $logger;
    private ContaoFramework $framework;

    public function __construct(
        ActiveCampaignService $activeCampaignService,
        DelayedTransferStorage $storage,
        LoggerInterface $logger,
        ContaoFramework $framework
    ) {
        $this->activeCampaignService = $activeCampaignService;
        $this->storage = $storage;
        $this->logger = $logger;
        $this->framework = $framework;
    }

    /**
     * Hook: processFormData (Priority: 0)
     * Wird aufgerufen nachdem ein Formular erfolgreich abgeschickt wurde
     * und NACH der E-Mail-Erstellung
     */
    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        $this->framework->initialize();
        $formId = (int)$form->id;

        $this->log('FormSubmitListener called for form ' . $formId, __METHOD__);

        // Config aus submittedData (wurde von PrepareTransferTokenListener gesetzt)
        // ODER aus GLOBALS (falls CleanupListener es entfernt hat)
        $config = $submittedData['_ac_config']
            ?? $GLOBALS['ACTIVECAMPAIGN_CONFIG']
            ?? $GLOBALS['ACTIVECAMPAIGN_FORMS'][$formId]
            ?? null;

        if (!$config) {
            $this->log('No AC config found for form ' . $formId, __METHOD__);
            return;
        }

        $delayedTransfer = $config['delayed_transfer'] ?? false;
        $isSpam = isset($GLOBALS['C2N_SPAM_DETECTED'][$formId]);

        // Bei DIRECT TRANSFER: SPAM-Check
        if (!$delayedTransfer && $isSpam) {
            $this->log('SPAM detected - skipping direct AC transfer for form ' . $formId, __METHOD__, 'error');
            return;
        }

        try {
            // E-Mail-Adresse extrahieren
            $email = $this->extractEmail($submittedData);

            if (!$email) {
                $this->log('No email address found in form data', __METHOD__, 'error');
                return;
            }

            // Contact-Daten vorbereiten
            $contactData = $this->prepareContactData($submittedData, $email);

            if ($delayedTransfer) {
                // DELAYED TRANSFER: In DB speichern (AUCH bei SPAM!)
                $this->handleDelayedTransfer(
                    $submittedData,
                    $formId,
                    $email,
                    $contactData,
                    $config,
                    $isSpam
                );
            } else {
                // DIRECT TRANSFER: Sofort zu ActiveCampaign
                $this->handleDirectTransfer(
                    $email,
                    $contactData,
                    $config
                );
            }

        } catch (\Exception $e) {
            $this->log('ActiveCampaign processing failed: ' . $e->getMessage(), __METHOD__, 'error');
        }
    }

    /**
     * Behandelt direkten Transfer zu ActiveCampaign
     */
    private function handleDirectTransfer(
        string $email,
        array $contactData,
        array $config
    ): void {
        $result = $this->activeCampaignService->createOrUpdateContact(
            $contactData,
            $config['list_id'],
            $config['tags']
        );

        $this->log(
            'SUCCESS: Contact ' . $email . ' transferred to ActiveCampaign (ID: ' . ($result['id'] ?? 'unknown') . ')',
            __METHOD__
        );
    }

    /**
     * Behandelt manuellen Transfer (Delayed Transfer)
     *
     * WICHTIG: Wird AUCH bei SPAM ausgeführt!
     * Der Redakteur entscheidet manuell ob die Daten übertragen werden.
     */
    private function handleDelayedTransfer(
        array $submittedData,
        int $formId,
        string $email,
        array $contactData,
        array $config,
        bool $isSpam
    ): void {
        // Token aus submittedData ODER GLOBALS (Fallback nach CleanupListener)
        $token = $submittedData['_ac_transfer_token']
            ?? $GLOBALS['ACTIVECAMPAIGN_TRANSFER_TOKEN']
            ?? null;

        $transferUrl = $submittedData['_ac_transfer_url']
            ?? $GLOBALS['ACTIVECAMPAIGN_TRANSFER_URL']
            ?? null;

        if (!$token || !$transferUrl) {
            $this->log(
                'ERROR: Transfer token missing for form ' . $formId . ' - PrepareTransferTokenListener did not run?',
                __METHOD__,
                'error'
            );
            return;
        }

        // In Datenbank speichern
        $autoDeleteDays = $config['auto_delete_days'] ?? 10;

        $id = $this->storage->save(
            $token,
            $formId,
            $email,
            $contactData,
            $config['list_id'],
            $config['tags'],
            $autoDeleteDays
        );

        // Log mit SPAM-Info
        $logMessage = $isSpam
            ? 'Delayed transfer saved (SPAM marked): ' . $email
            : 'SUCCESS: Delayed transfer saved: ' . $email;

        $this->log($logMessage . ' (Token: ' . substr($token, 0, 8) . '..., ID: ' . $id . ')', __METHOD__);
    }

    /**
     * Loggt in Contao System-Log (4.13 + 5.x kompatibel)
     */
    private function log(string $message, string $method, string $level = 'info'): void
    {
        // PSR-Logger
        if ($level === 'error') {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        // Contao System-Log (try-catch für 4.13/5.x Kompatibilität)
        try {
            $contaoLevel = ($level === 'error') ? 'error' : TL_GENERAL;
            System::log($message, $method, $contaoLevel);
        } catch (\Throwable $e) {
            // ToDo: Log auf Context umstellen https://docs.contao.org/5.x/dev/framework/logging/
        }
    }

    /**
     * Extrahiert die E-Mail-Adresse aus den Formulardaten
     */
    private function extractEmail(array $data): ?string
    {
        $emailFields = ['email', 'e-mail', 'e_mail', 'mail', 'Email', 'E-Mail'];

        foreach ($emailFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $value = $data[$field];
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Bereitet die Kontakt-Daten für ActiveCampaign vor
     */
    private function prepareContactData(array $data, string $email): array
    {
        $contactData = ['email' => $email];

        // Standard-Felder mappen
        $fieldMapping = [
            'firstName' => ['firstname', 'firstName', 'first_name', 'vorname', 'name', 'Name', 'Vorname'],
            'lastName' => ['lastname', 'lastName', 'last_name', 'nachname', 'surname', 'Nachname'],
            'phone' => ['phone', 'telefon', 'telephone', 'tel', 'Telefon', 'Tel', 'Telefonnummer']
        ];

        foreach ($fieldMapping as $acField => $possibleNames) {
            foreach ($possibleNames as $fieldName) {
                if (isset($data[$fieldName]) && !empty($data[$fieldName])) {
                    $contactData[$acField] = $data[$fieldName];
                    break;
                }
            }
        }

        // Custom Fields (acf_ID Format)
        $customFields = [];

        foreach ($data as $key => $value) {
            // Skip leere Werte und System-Felder
            if (empty($value) || in_array($key, ['FORM_SUBMIT', 'REQUEST_TOKEN', 'submit', '_ac_transfer_token', '_ac_transfer_url', '_ac_config'])) {
                continue;
            }

            // Custom Field Format: acf_123
            if (preg_match('/^acf_(\d+)$/', $key, $matches)) {
                $fieldId = (int)$matches[1];
                $apiValue = is_array($value) ? implode(', ', $value) : $value;

                $customFields[] = [
                    'field' => $fieldId,
                    'value' => $apiValue
                ];
            }
        }

        if (!empty($customFields)) {
            $contactData['fieldValues'] = $customFields;
        }

        return $contactData;
    }
}