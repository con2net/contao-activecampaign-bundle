<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/EventListener/PrepareTransferTokenListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Con2net\ContaoActiveCampaignBundle\Service\TokenGenerator;
use Contao\Form;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * prepareFormData Hook Listener (Priority 50)
 * Generiert Transfer-Token VOR der E-Mail-Erstellung
 *
 * WICHTIG: Läuft IMMER - auch bei SPAM!
 * Bei Delayed Transfer brauchen wir den Token für das E-Mail.
 */
class PrepareTransferTokenListener
{
    private TokenGenerator $tokenGenerator;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        TokenGenerator $tokenGenerator,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Hook: prepareFormData (Priority: 50)
     *
     * WICHTIG für Contao 4.13:
     * - array &$submittedData (MIT & für Pass-by-Reference!)
     * - Return: void
     */
    public function __invoke(
        array &$submittedData,
        array $labels,
        array $fields,
        Form $form
    ): void {
        $formId = (int)$form->id;

        $this->logger->debug('PrepareTransferToken Hook called for form ' . $formId);

        // Config aus Datenbank laden (nicht aus GLOBALS - die sind beim POST leer!)
        $config = $this->getActiveCampaignConfig($formId);

        if (!$config) {
            $this->logger->debug('No AC config found for form ' . $formId);
            return;
        }

        // Nur wenn Delayed Transfer aktiviert ist
        if (!$config['delayed_transfer']) {
            $this->logger->debug('Delayed Transfer not active for form ' . $formId);
            return;
        }

        // KEIN SPAM-CHECK hier!
        // Bei Delayed Transfer brauchen wir den Token IMMER für das E-Mail.
        // Der Redakteur entscheidet später ob er die Daten überträgt.

        // Token generieren
        $token = $this->tokenGenerator->generate(32);
        $transferUrl = \Contao\Environment::get('url') . '/activecampaign/transfer/' . $token;

        $this->logger->info('Transfer token generated: ' . substr($token, 0, 8) . '...');

        // =====================================================================
        // WICHTIG für NC 1.x:
        // NC 1.x erstellt ##form_FELDNAME## Tokens direkt aus $submittedData!
        // Deshalb MUSS der Key OHNE Unterstrich sein!
        // =====================================================================

        // Für NC 1.x: Direkt in submittedData → wird zu ##form_activecampaign_transfer_link##
        $submittedData['activecampaign_transfer_link'] = $transferUrl;

        // Interne Keys für FormSubmitListener (mit Unterstrich, wird von NC ignoriert)
        $submittedData['_ac_transfer_token'] = $token;
        $submittedData['_ac_transfer_url'] = $transferUrl;

        // Config auch für FormSubmitListener
        $submittedData['_ac_config'] = $config;

        // Zusätzlich in GLOBALS setzen (für andere Listener/Fallback)
        $GLOBALS['TL_SIMPLE_TOKENS']['activecampaign_transfer_link'] = $transferUrl;
        $GLOBALS['ACTIVECAMPAIGN_TRANSFER_LINK'] = $transferUrl;

        $this->logger->info('Transfer token set in submittedData for NC 1.x: activecampaign_transfer_link');
    }

    /**
     * Lädt ActiveCampaign Config aus Datenbank
     *
     * WICHTIG: Config MUSS aus DB geladen werden!
     * Der ContentElement Controller läuft nur beim GET (Seite laden),
     * NICHT beim POST (Form Submit). Deshalb ist $GLOBALS['ACTIVECAMPAIGN_FORMS'] leer!
     */
    private function getActiveCampaignConfig(int $formId): ?array
    {
        try {
            // Suche Content Element mit diesem Formular
            $result = $this->connection->fetchAssociative(
                'SELECT c2n_ac_list_id, c2n_ac_tags, c2n_ac_delay_transfer, c2n_ac_auto_delete_days
                 FROM tl_content 
                 WHERE type = ? AND c2n_ac_form_id = ?
                 LIMIT 1',
                ['activecampaign_form', $formId]
            );

            if (!$result) {
                return null;
            }

            return [
                'list_id' => $result['c2n_ac_list_id'] ?? '',
                'tags' => $result['c2n_ac_tags'] ? array_map('trim', explode(',', $result['c2n_ac_tags'])) : [],
                'delayed_transfer' => (bool)$result['c2n_ac_delay_transfer'],
                'auto_delete_days' => (int)($result['c2n_ac_auto_delete_days'] ?: 10),
                'debug' => false
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to load AC config: ' . $e->getMessage());
            return null;
        }
    }
}