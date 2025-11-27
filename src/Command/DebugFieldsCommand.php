<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Command/DebugFieldsCommand.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Debug-Command zum Auslesen aller ActiveCampaign Felder
 *
 * Verwendung: php vendor/bin/contao-console activecampaign:debug-fields
 */
class DebugFieldsCommand extends Command
{
    private string $apiUrl;
    private string $apiKey;

    protected static $defaultName = 'activecampaign:debug-fields';
    protected static $defaultDescription = 'List all ActiveCampaign fields with their IDs';

    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command lists all available ActiveCampaign fields with their IDs and types.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ActiveCampaign Fields Debug');

        try {
            // 1. Alle Felder abrufen
            $io->section('Fetching all fields from ActiveCampaign...');
            $fields = $this->getFields();

            if (empty($fields)) {
                $io->warning('No fields found!');
                return Command::FAILURE;
            }

            // 2. Standard-Felder
            $io->section('Standard Fields (use these names directly):');
            $io->table(
                ['Field', 'API Name', 'Usage in Contao Form'],
                [
                    ['E-Mail', 'email', 'Name your field: email'],
                    ['Vorname', 'firstName', 'Name your field: firstName'],
                    ['Nachname', 'lastName', 'Name your field: lastName'],
                    ['Telefon', 'phone', 'Name your field: phone'],
                ]
            );

            // 3. Custom Fields
            $io->section('Custom Fields in your ActiveCampaign:');

            $customFieldsTable = [];
            foreach ($fields as $field) {
                $customFieldsTable[] = [
                    $field['id'],
                    $field['title'],
                    $field['type'],
                    'acf_' . $field['id']  // Zeige direkt das Contao-Feldname-Format!
                ];
            }

            $io->table(
                ['ID', 'Title', 'Type', 'Use in Contao Form'],
                $customFieldsTable
            );

            // 4. Mapping-Empfehlung
            $io->section('How to use Custom Fields in Contao:');
            $io->text('Name your Contao form fields using the format: acf_ID');
            $io->newLine();
            $io->text('Examples:');
            $io->listing([
                'Field ID 6 (Company) → Contao field name: acf_6',
                'Field ID 18 (City) → Contao field name: acf_18',
                'Field ID 10 (Country) → Contao field name: acf_10'
            ]);

            $io->note('The acf_ prefix tells the bundle to map this field to the ActiveCampaign custom field with that ID.');

            // 5. Tags testen
            $io->section('Testing Tags...');
            $tags = $this->getTags();

            if (!empty($tags)) {
                $io->text(sprintf('Found %d existing tags:', count($tags)));
                $tagNames = array_map(function($tag) {
                    return $tag['tag'] . ' (ID: ' . $tag['id'] . ')';
                }, array_slice($tags, 0, 10));
                $io->listing($tagNames);
            } else {
                $io->text('No tags found yet.');
            }

            $io->success('Field analysis complete!');

            // Speichere die Felder auch ine ine Datei
            $outputFile = 'var/activecampaign-fields.json';
            file_put_contents($outputFile, json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'standard_fields' => [
                    'email', 'firstName', 'lastName', 'phone'
                ],
                'custom_fields' => $fields,
                'tags' => $tags
            ], JSON_PRETTY_PRINT));

            $io->note('Field data also saved to: ' . $outputFile);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getFields(): array
    {
        $response = $this->apiRequest('GET', '/api/3/fields?limit=100');
        return $response['fields'] ?? [];
    }

    private function getTags(): array
    {
        $response = $this->apiRequest('GET', '/api/3/tags?limit=100');
        return $response['tags'] ?? [];
    }

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

        if ($httpCode >= 400) {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            throw new \Exception('API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        return $responseData;
    }
}