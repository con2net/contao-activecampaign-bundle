<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Migration/Version20251119000000.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Migration: Erstellt Tabelle für Delayed Transfer Feature
 *
 * Tabelle: tl_c2n_activecampaign
 */
class Version20251119000000 extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Prüft ob die Migration ausgeführt werden muss
     * WICHTIG: Return-Type muss MigrationResult sein!
     */
    public function shouldRun(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Prüfen ob Tabelle bereits existiert
        if ($schemaManager->tablesExist(['tl_c2n_activecampaign'])) {
            return MigrationResult::createSkipped('Table tl_c2n_activecampaign already exists');
        }

        return MigrationResult::createPending('Table tl_c2n_activecampaign needs to be created');
    }

    /**
     * Führt die Migration aus
     */
    public function run(): MigrationResult
    {
        $sql = "
            CREATE TABLE tl_c2n_activecampaign (
                id int(10) unsigned NOT NULL auto_increment,
                tstamp int(10) unsigned NOT NULL default '0',
                
                token varchar(64) NOT NULL default '',
                form_id int(10) unsigned NOT NULL default '0',
                email varchar(255) NOT NULL default '',
                
                created_at int(10) unsigned NOT NULL default '0',
                processed_at int(10) unsigned NULL,
                auto_delete_at int(10) unsigned NOT NULL default '0',
                
                json_data text NULL,
                status varchar(20) NOT NULL default 'pending',
                
                PRIMARY KEY (id),
                UNIQUE KEY token (token),
                KEY form_id (form_id),
                KEY status (status),
                KEY auto_delete_at (auto_delete_at),
                KEY created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->connection->executeStatement($sql);

        return MigrationResult::createSuccessful('Table tl_c2n_activecampaign created successfully');
    }
}
