<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Controller/FieldsViewController.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller für Browser-Ansicht der ActiveCampaign Felder
 * Zeigt Standard-Felder und Custom Fields in einer schönen HTML-Tabelle
 *
 * Aufruf: https://domain.de/activecampaign/fields
 */
class FieldsViewController extends AbstractController
{
    private string $apiUrl;
    private string $apiKey;
    private LoggerInterface $logger;

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
     * Zeigt alle ActiveCampaign Felder im Browser
     */
    public function view(): Response
    {
        try {
            // Felder abrufen
            $fields = $this->getFields();
            $tags = $this->getTags();

            // HTML rendern
            $html = $this->renderHtml($fields, $tags);

            return new Response($html);

        } catch (\Exception $e) {
            $this->logger->error('FieldsViewController error: ' . $e->getMessage());

            return new Response(
                $this->renderError($e->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Holt alle Custom Fields von ActiveCampaign
     */
    private function getFields(): array
    {
        $response = $this->apiRequest('GET', '/api/3/fields?limit=100');
        return $response['fields'] ?? [];
    }

    /**
     * Holt vorhandene Tags
     */
    private function getTags(): array
    {
        try {
            $response = $this->apiRequest('GET', '/api/3/tags?limit=20');
            return $response['tags'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Rendert die HTML-Seite
     */
    private function renderHtml(array $fields, array $tags): string
    {
        // Standard-Felder
        $standardFields = [
            ['field' => 'E-Mail', 'apiName' => 'email', 'usage' => 'email'],
            ['field' => 'Vorname', 'apiName' => 'firstName', 'usage' => 'firstName'],
            ['field' => 'Nachname', 'apiName' => 'lastName', 'usage' => 'lastName'],
            ['field' => 'Telefon', 'apiName' => 'phone', 'usage' => 'phone']
        ];

        $standardFieldsHtml = '';
        foreach ($standardFields as $field) {
            $standardFieldsHtml .= sprintf(
                '<tr><td><strong>%s</strong></td><td><code>%s</code></td><td><code>%s</code></td></tr>',
                htmlspecialchars($field['field']),
                htmlspecialchars($field['apiName']),
                htmlspecialchars($field['usage'])
            );
        }

        // Custom Fields
        $customFieldsHtml = '';
        if (empty($fields)) {
            $customFieldsHtml = '<tr><td colspan="4" style="text-align:center;color:#999;">Keine Custom Fields vorhanden</td></tr>';
        } else {
            foreach ($fields as $field) {
                $customFieldsHtml .= sprintf(
                    '<tr><td><strong>%s</strong></td><td>%s</td><td><code>acf_%s</code></td><td>%s</td></tr>',
                    htmlspecialchars($field['id'] ?? ''),
                    htmlspecialchars($field['title'] ?? ''),
                    htmlspecialchars($field['id'] ?? ''),
                    htmlspecialchars($field['type'] ?? '')
                );
            }
        }

        // Tags
        $tagsHtml = '';
        if (empty($tags)) {
            $tagsHtml = '<li style="color:#999;">Noch keine Tags vorhanden</li>';
        } else {
            // ALLE Tags anzeigen
            foreach ($tags as $tag) {
                $tagsHtml .= sprintf(
                    '<li>%s <span style="color:#999;">(ID: %s)</span></li>',
                    htmlspecialchars($tag['tag'] ?? ''),
                    htmlspecialchars($tag['id'] ?? '')
                );
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ActiveCampaign Felder</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        h2 {
            color: #34495e;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2:first-of-type {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
        }
        th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:hover {
            background: #f8f9fa;
        }
        code {
            background: #ecf0f1;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: "Courier New", monospace;
            font-size: 14px;
            color: #e74c3c;
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
        }
        ul {
            margin: 10px 0 20px 20px;
        }
        li {
            margin: 5px 0;
        }
        .close-button {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .close-button:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ActiveCampaign Felder</h1>
        <p class="subtitle">Übersicht aller verfügbaren Felder für die Contao-Integration</p>

        <h2>Standard-Felder</h2>
        <p>Diese Felder können direkt verwendet werden:</p>
        <table>
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>API Name</th>
                    <th>Verwendung in Contao Formular</th>
                </tr>
            </thead>
            <tbody>
                {$standardFieldsHtml}
            </tbody>
        </table>

        <h2>Custom Fields</h2>
        <p>Für Custom Fields verwende das Format: <code>acf_ID</code></p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Verwendung in Contao</th>
                    <th>Typ</th>
                </tr>
            </thead>
            <tbody>
                {$customFieldsHtml}
            </tbody>
        </table>

        <div class="info-box">
            <p><strong>Beispiel:</strong></p>
            <p>Wenn dein Custom Field die ID <code>6</code> hat, benenne dein Contao-Formularfeld: <code>acf_6</code></p>
        </div>

        <h2>Vorhandene Tags</h2>
        <p>Diese Tags existieren bereits in deinem ActiveCampaign Account:</p>
        <ul>
            {$tagsHtml}
        </ul>

        <a href="javascript:window.close();" class="close-button">Fenster schließen</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Rendert Fehler-Seite
     */
    private function renderError(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fehler</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Fehler</h1>
        <p>{$message}</p>
        <p style="margin-top:20px;">Bitte prüfe deine ActiveCampaign API-Konfiguration.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * API-Request zu ActiveCampaign
     */
    private function apiRequest(string $method, string $endpoint): array
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Api-Token: ' . $this->apiKey,
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

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
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            throw new \Exception('API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        return $responseData;
    }
}