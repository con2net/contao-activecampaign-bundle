<?php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Controller;

use Con2net\ContaoActiveCampaignBundle\Service\ActiveCampaignService;
use Con2net\ContaoActiveCampaignBundle\Service\DelayedTransferStorage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller für verzögerte ActiveCampaign Transfers
 * Wird aufgerufen wenn Admin auf Transfer-Link in E-Mail klickt
 *
 * WICHTIG: System::log() Aufrufe sind zuerst try-catch für 5.3 Kompatibilität abgesichert!
 * ToDo: Log auf Context umstellen https://docs.contao.org/5.x/dev/framework/logging/
 * Pfad: vendor/con2net/contao-activecampaign-bundle/src/Controller/TransferController.php
 */
class TransferController extends AbstractController
{
    private DelayedTransferStorage $storage;
    private ActiveCampaignService $activeCampaignService;
    private LoggerInterface $logger;

    public function __construct(
        DelayedTransferStorage $storage,
        ActiveCampaignService $activeCampaignService,
        LoggerInterface $logger
    ) {
        $this->storage = $storage;
        $this->activeCampaignService = $activeCampaignService;
        $this->logger = $logger;
    }

    /**
     * Verarbeitet den Transfer-Request
     * Route: /activecampaign/transfer/{token}
     */
    public function execute(string $token): Response
    {
        $this->logger->info('Transfer request received', [
            'token' => substr($token, 0, 8) . '...'
        ]);

        // Token validieren
        if (!$this->storage->isValidToken($token)) {
            $this->logger->warning('Invalid or expired token', [
                'token' => substr($token, 0, 8) . '...'
            ]);

            return new Response($this->renderErrorPage(
                'Ungültiger oder abgelaufener Link',
                'Dieser Transfer-Link ist nicht mehr gültig. Möglicherweise wurde er bereits verwendet oder ist abgelaufen.'
            ), Response::HTTP_NOT_FOUND);
        }

        // Transfer-Daten laden
        $transferData = $this->storage->load($token);

        if (!$transferData) {
            $this->logger->error('Could not load transfer data', [
                'token' => substr($token, 0, 8) . '...'
            ]);

            return new Response($this->renderErrorPage(
                'Daten nicht gefunden',
                'Die Transfer-Daten konnten nicht geladen werden. Bitte kontaktieren Sie den Administrator.'
            ), Response::HTTP_NOT_FOUND);
        }

        try {
            // Zu ActiveCampaign übertragen
            $result = $this->activeCampaignService->createOrUpdateContact(
                $transferData['contactData'],
                $transferData['listId'],
                $transferData['tags']
            );

            // Als verarbeitet markieren
            $this->storage->markAsProcessed($token);

            $email = $transferData['email'] ?? $transferData['contactData']['email'] ?? 'unknown';
            $contactId = $result['id'] ?? null;

            $this->logger->info('Transfer completed successfully', [
                'email' => $email,
                'contact_id' => $contactId
            ]);

            // System-Log für Contao 4.13 (try-catch für 5.3 Kompatibilität)
            try {
                \Contao\System::log(
                    'Delayed transfer completed: ' . $email . ' (Contact ID: ' . ($contactId ?? 'unknown') . ')',
                    __METHOD__,
                    TL_GENERAL
                );
            } catch (\Throwable $e) {
                // In Contao 5.3 existiert System::log() nicht - ignorieren
            }

            // Erfolgsseite
            return new Response($this->renderSuccessPage(
                $email,
                $contactId,
                $transferData['listId']
            ));

        } catch (\Exception $e) {
            $this->logger->error('Transfer failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 8) . '...'
            ]);

            // System-Log für Contao 4.13 (try-catch für 5.3 Kompatibilität)
            try {
                \Contao\System::log(
                    'Transfer failed: ' . $e->getMessage(),
                    __METHOD__,
                    TL_ERROR
                );
            } catch (\Throwable $e) {
                // In Contao 5.3 existiert System::log() nicht - ignorieren
            }

            return new Response($this->renderErrorPage(
                'Übertragung fehlgeschlagen',
                'Die Daten konnten nicht zu ActiveCampaign übertragen werden: ' . $e->getMessage()
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rendert eine Erfolgsseite
     */
    private function renderSuccessPage(string $email, int|string|null $contactId, string $listId): string
    {
        return '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transfer erfolgreich</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            width: 60px;
            height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon svg {
            width: 35px;
            height: 35px;
            stroke: white;
            stroke-width: 3;
            fill: none;
        }
        h1 {
            color: #28a745;
            margin: 0 0 15px;
            font-size: 24px;
        }
        p {
            color: #666;
            line-height: 1.5;
            margin: 10px 0;
        }
        .details {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
        }
        .details strong {
            color: #333;
            display: inline-block;
            min-width: 100px;
        }
        .close-hint {
            color: #999;
            font-size: 13px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <svg viewBox="0 0 50 50"><path d="M 10 25 L 20 35 L 40 15" /></svg>
        </div>
        <h1>Erfolgreich übertragen!</h1>
        <p>Die Kontaktdaten wurden zu ActiveCampaign übertragen.</p>
        
        <div class="details">
            <p><strong>E-Mail:</strong> ' . htmlspecialchars($email) . '</p>
            ' . ($contactId ? '<p><strong>Contact ID:</strong> ' . $contactId . '</p>' : '') . '
            <p><strong>Liste:</strong> ' . htmlspecialchars($listId) . '</p>
        </div>
        
        <p class="close-hint">Du kannst dieses Fenster jetzt schließen.</p>
    </div>
</body>
</html>';
    }

    /**
     * Rendert eine Fehlerseite
     */
    private function renderErrorPage(string $title, string $message): string
    {
        return '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            width: 60px;
            height: 60px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .error-icon svg {
            width: 35px;
            height: 35px;
            stroke: white;
            stroke-width: 3;
            fill: none;
        }
        h1 {
            color: #dc3545;
            margin: 0 0 15px;
            font-size: 24px;
        }
        p {
            color: #666;
            line-height: 1.5;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            <svg viewBox="0 0 50 50"><path d="M 15 15 L 35 35 M 35 15 L 15 35" /></svg>
        </div>
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>' . htmlspecialchars($message) . '</p>
    </div>
</body>
</html>';
    }
}