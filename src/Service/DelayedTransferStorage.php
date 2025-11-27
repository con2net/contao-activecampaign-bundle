<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Service/DelayedTransferStorage.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Storage Service für Delayed Transfer Feature
 * Verwaltet Pending Transfers in der Datenbank
 *
 * WICHTIG: System::log() Aufrufe sind mit try-catch für 5.3 Kompatibilität abgesichert!
 * ToDo: Log auf Context umstellen https://docs.contao.org/5.x/dev/framework/logging/
 */
class DelayedTransferStorage
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Speichert Formulardaten für verzögerte Übertragung
     */
    public function save(
        string $token,
        int $formId,
        string $email,
        array $contactData,
        string $listId,
        array $tags = [],
        int $autoDeleteDays = 10
    ): int {
        $now = time();
        $autoDeleteAt = $now + ($autoDeleteDays * 86400);

        $jsonData = json_encode([
            'contactData' => $contactData,
            'listId' => $listId,
            'tags' => $tags,
            'formId' => $formId,
            'email' => $email
        ], JSON_PRETTY_PRINT);

        $this->connection->insert('tl_c2n_activecampaign', [
            'tstamp' => $now,
            'token' => $token,
            'form_id' => $formId,
            'email' => $email,
            'created_at' => $now,
            'processed_at' => null,
            'auto_delete_at' => $autoDeleteAt,
            'json_data' => $jsonData,
            'status' => 'pending'
        ]);

        $id = (int)$this->connection->lastInsertId();

        $this->logger->info('Delayed transfer saved', [
            'id' => $id,
            'token' => substr($token, 0, 8) . '...',
            'email' => $email,
            'auto_delete' => date('d.m.Y', $autoDeleteAt)
        ]);

        return $id;
    }

    /**
     * Lädt einen Pending Transfer anhand des Tokens
     */
    public function load(string $token): ?array
    {
        $result = $this->connection->fetchAssociative(
            'SELECT * FROM tl_c2n_activecampaign WHERE token = ? AND status = ?',
            [$token, 'pending']
        );

        if (!$result) {
            $this->logger->warning('Transfer not found or already processed', [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return null;
        }

        // Prüfen ob abgelaufen
        if ($result['auto_delete_at'] < time()) {
            $this->markAsExpired((int)$result['id']);
            $this->logger->warning('Transfer expired', [
                'token' => substr($token, 0, 8) . '...',
                'expired_at' => date('d.m.Y H:i', $result['auto_delete_at'])
            ]);
            return null;
        }

        // JSON-Daten dekodieren
        $jsonData = json_decode($result['json_data'], true);
        if (!$jsonData) {
            $this->logger->error('Invalid JSON data for token ' . substr($token, 0, 8) . '...');
            return null;
        }

        return array_merge($result, $jsonData);
    }

    /**
     * Markiert einen Transfer als verarbeitet
     */
    public function markAsProcessed(string $token): bool
    {
        $affected = $this->connection->update(
            'tl_c2n_activecampaign',
            [
                'tstamp' => time(),
                'processed_at' => time(),
                'status' => 'processed'
            ],
            ['token' => $token]
        );

        if ($affected > 0) {
            $this->logger->info('Transfer marked as processed', [
                'token' => substr($token, 0, 8) . '...'
            ]);

            // System-Log für Contao 4.13 (try-catch für 5.3 Kompatibilität)
            try {
                \Contao\System::log(
                    'Transfer processed: ' . substr($token, 0, 8) . '...',
                    __METHOD__,
                    TL_GENERAL
                );
            } catch (\Throwable $e) {
                // ignorieren
            }

            return true;
        }

        return false;
    }

    /**
     * Markiert einen Transfer als abgelaufen
     */
    public function markAsExpired(int $id): bool
    {
        $affected = $this->connection->update(
            'tl_c2n_activecampaign',
            [
                'tstamp' => time(),
                'status' => 'expired'
            ],
            ['id' => $id]
        );

        return $affected > 0;
    }

    /**
     * Löscht einen Transfer
     */
    public function delete(string $token): bool
    {
        $affected = $this->connection->delete(
            'tl_c2n_activecampaign',
            ['token' => $token]
        );

        if ($affected > 0) {
            $this->logger->info('Transfer deleted', [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Löscht alle abgelaufenen Transfers (für Cronjob)
     */
    public function cleanup(): int
    {
        $now = time();

        // Erst auf 'expired' setzen
        $this->connection->executeStatement(
            'UPDATE tl_c2n_activecampaign 
             SET status = ?, tstamp = ? 
             WHERE auto_delete_at < ? AND status = ?',
            ['expired', $now, $now, 'pending']
        );

        // Dann löschen
        $affected = $this->connection->executeStatement(
            'DELETE FROM tl_c2n_activecampaign 
             WHERE auto_delete_at < ? AND status = ?',
            [$now, 'expired']
        );

        if ($affected > 0) {
            $this->logger->info('Cleanup: ' . $affected . ' expired transfers deleted');
        }

        return $affected;
    }

    /**
     * Gibt alle Pending Transfers zurück (für Backend-Übersicht)
     */
    public function getPending(int $limit = 100): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_c2n_activecampaign 
             WHERE status = ? 
             ORDER BY created_at DESC 
             LIMIT ?',
            ['pending', $limit],
            ['string', 'integer']
        );
    }

    /**
     * Zählt Pending Transfers
     */
    public function countPending(): int
    {
        return (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_c2n_activecampaign WHERE status = ?',
            ['pending']
        );
    }

    /**
     * Prüft ob ein Token existiert und gültig ist
     */
    public function isValidToken(string $token): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_c2n_activecampaign 
             WHERE token = ? AND status = ? AND auto_delete_at > ?',
            [$token, 'pending', time()]
        );

        return (int)$result > 0;
    }
}