<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Service/ActiveCampaignService.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Service;

use Psr\Log\LoggerInterface;

/**
 * Service für die Kommunikation mit der ActiveCampaign API
 * Erstellt/aktualisiert Kontakte, fügt zu Listen hinzu und verwaltet Tags
 *
 * WICHTIG: Verwendet nur PSR-Logger (kein LoggingHelper für Frontend-Kompatibilität!)
 * ToDo: Log auf Context umstellen https://docs.contao.org/5.x/dev/framework/logging/
 */
class ActiveCampaignService
{
    private string $apiUrl;
    private string $apiKey;
    private LoggerInterface $logger;
    private bool $debugMode = false;

    public function __construct(
        string $apiUrl,
        string $apiKey,
        LoggerInterface $logger
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * Aktiviert Debug-Modus für detailliertes Logging
     */
    public function setDebugMode(bool $debug): void
    {
        $this->debugMode = $debug;
    }

    /**
     * Erstellt oder aktualisiert einen Kontakt in ActiveCampaign
     * Die sync-API übernimmt das automatisch basierend auf der E-Mail
     */
    public function createOrUpdateContact(array $contactData, string $listId, array $tags = []): array
    {
        if (!isset($contactData['email'])) {
            throw new \InvalidArgumentException('E-Mail-Adresse ist erforderlich');
        }

        try {
            // 1. Kontakt synchronisieren (erstellt ODER aktualisiert automatisch)
            if ($this->debugMode) {
                $this->logger->info('Syncing contact with ActiveCampaign...');
            }

            $contact = $this->syncContact($contactData);
            $contactId = $contact['id'] ?? null;

            if (!$contactId) {
                throw new \Exception('Contact sync failed - no ID returned');
            }

            if ($this->debugMode) {
                $this->logger->info('Contact synced', ['contact_id' => $contactId]);
            }

            // 2. Kontakt zur Liste hinzufügen (nur wenn Liste angegeben)
            if ($listId && $listId !== '0') {
                try {
                    $isInList = $this->isContactInList((int)$contactId, $listId);

                    if (!$isInList) {
                        $this->addContactToList((int)$contactId, $listId);

                        if ($this->debugMode) {
                            $this->logger->info('Contact added to list', ['list_id' => $listId]);
                        }
                    } else {
                        if ($this->debugMode) {
                            $this->logger->info('Contact already in list', ['list_id' => $listId]);
                        }
                    }
                } catch (\Exception $e) {
                    // Fehler beim Listen-Hinzufügen nicht als kritisch behandeln
                    if ($this->debugMode) {
                        $this->logger->warning('Could not add to list', ['error' => $e->getMessage()]);
                    }
                }
            }

            // 3. Tags hinzufügen (falls vorhanden)
            if (!empty($tags)) {
                if ($this->debugMode) {
                    $this->logger->info('Processing tags...');
                }

                $this->addTagsToContact((int)$contactId, $tags);

                if ($this->debugMode) {
                    $this->logger->info('Tags processed');
                }
            }

            return $contact;

        } catch (\Exception $e) {
            $this->logger->error('ActiveCampaign API error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Synchronisiert einen Kontakt (erstellt oder aktualisiert automatisch)
     */
    private function syncContact(array $contactData): array
    {
        $apiData = [
            'email' => $contactData['email']
        ];

        // Standard-Felder
        $standardFields = ['firstName', 'lastName', 'phone'];
        foreach ($standardFields as $field) {
            if (isset($contactData[$field]) && !empty($contactData[$field])) {
                $apiData[$field] = $contactData[$field];
            }
        }

        // Custom Fields
        $fieldValues = [];
        if (isset($contactData['fieldValues']) && is_array($contactData['fieldValues'])) {
            $fieldValues = $contactData['fieldValues'];
        }

        // Zusätzliche Felder die nicht Standard sind als Custom Fields behandeln
        foreach ($contactData as $key => $value) {
            if (!in_array($key, ['email', 'firstName', 'lastName', 'phone', 'fieldValues']) && !empty($value)) {
                $fieldValues[] = [
                    'field' => $key,
                    'value' => $value
                ];
            }
        }

        $payload = [
            'contact' => $apiData
        ];

        if (!empty($fieldValues)) {
            $payload['contact']['fieldValues'] = $fieldValues;
        }

        if ($this->debugMode) {
            $this->logger->debug('API Request: POST /api/3/contact/sync', ['payload' => $payload]);
        }

        $response = $this->apiRequest('POST', '/api/3/contact/sync', $payload);

        if (!isset($response['contact'])) {
            throw new \Exception('Invalid API response - no contact data');
        }

        if ($this->debugMode) {
            $this->logger->debug('API Response received', ['contact_id' => $response['contact']['id'] ?? null]);
        }

        return $response['contact'];
    }

    /**
     * Prüft ob ein Kontakt bereits in einer Liste ist
     */
    private function isContactInList(int $contactId, string $listId): bool
    {
        try {
            $response = $this->apiRequest('GET', '/api/3/contacts/' . $contactId . '/contactLists');

            if (isset($response['contactLists']) && is_array($response['contactLists'])) {
                foreach ($response['contactLists'] as $contactList) {
                    if ($contactList['list'] == $listId && $contactList['status'] == 1) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorieren
        }

        return false;
    }

    /**
     * Fügt einen Kontakt zu einer Liste hinzu
     */
    private function addContactToList(int $contactId, string $listId): array
    {
        $payload = [
            'contactList' => [
                'list' => (int)$listId,
                'contact' => $contactId,
                'status' => 1
            ]
        ];

        return $this->apiRequest('POST', '/api/3/contactLists', $payload);
    }

    /**
     * Fügt einem Kontakt Tags hinzu
     */
    private function addTagsToContact(int $contactId, array $tags): void
    {
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }

            try {
                $tag = $this->createOrGetTag($tagName);
                $tagId = $tag['id'] ?? null;

                if (!$tagId) {
                    continue;
                }

                $this->assignTagToContact($contactId, (int)$tagId);

                if ($this->debugMode) {
                    $this->logger->info('Tag assigned', ['tag' => $tagName, 'contact_id' => $contactId]);
                }

            } catch (\Exception $e) {
                if ($this->debugMode) {
                    $this->logger->warning('Failed to add tag', ['tag' => $tagName, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Erstellt einen Tag oder gibt einen existierenden zurück
     */
    private function createOrGetTag(string $tagName): array
    {
        // Erst versuchen den Tag zu finden
        try {
            $searchResponse = $this->apiRequest('GET', '/api/3/tags?search=' . urlencode($tagName));

            if (isset($searchResponse['tags']) && is_array($searchResponse['tags'])) {
                foreach ($searchResponse['tags'] as $tag) {
                    if (strtolower($tag['tag']) === strtolower($tagName)) {
                        return $tag;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorieren, versuchen zu erstellen
        }

        // Tag nicht gefunden, neu erstellen
        $payload = [
            'tag' => [
                'tag' => $tagName,
                'tagType' => 'contact',
                'description' => 'Automatisch erstellt via Contao'
            ]
        ];

        $response = $this->apiRequest('POST', '/api/3/tags', $payload);

        if (!isset($response['tag'])) {
            throw new \Exception('Invalid tag response');
        }

        return $response['tag'];
    }

    /**
     * Weist einem Kontakt einen Tag zu
     */
    private function assignTagToContact(int $contactId, int $tagId): array
    {
        $payload = [
            'contactTag' => [
                'contact' => $contactId,
                'tag' => $tagId
            ]
        ];

        return $this->apiRequest('POST', '/api/3/contactTags', $payload);
    }

    /**
     * Führt einen API-Request zu ActiveCampaign aus
     */
    private function apiRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Api-Token: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($data !== null) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        if ($httpCode >= 400) {
            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            throw new \Exception('API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        return $responseData;
    }
}