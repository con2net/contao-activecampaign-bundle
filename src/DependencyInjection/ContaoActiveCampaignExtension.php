<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/DependencyInjection/ContaoActiveCampaignExtension.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension-Klasse zum Laden der Service-Konfiguration
 *
 * Parameter aus ENV-Variablen
 *
 * Benötigte ENV-Variablen in .env oder .env.local:
 *   ACTIVECAMPAIGN_API_URL=https://ACCOUNT.api-us1.com
 *   ACTIVECAMPAIGN_API_KEY=dein-api-key
 */
class ContaoActiveCampaignExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Parameter aus ENV-Variablen setzen ===

        $container->setParameter(
            'con2net.activecampaign.api_url',
            '%env(string:ACTIVECAMPAIGN_API_URL)%'
        );

        $container->setParameter(
            'con2net.activecampaign.api_key',
            '%env(string:ACTIVECAMPAIGN_API_KEY)%'
        );

        // === Services laden ===
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');

        // === Conditional: NC 2.x SimpleTokenListener ===
        // Nur registrieren wenn Notification Center 2.x vorhanden ist
        // In NC 1.x (Contao 4.13) existiert diese Event-Klasse nicht
        if (!class_exists('Terminal42\NotificationCenterBundle\Event\CreateParserEvent')) {
            // NC 1.x oder kein NC - SimpleTokenListener entfernen
            if ($container->hasDefinition('Con2net\ContaoActiveCampaignBundle\EventListener\SimpleTokenListener')) {
                $container->removeDefinition('Con2net\ContaoActiveCampaignBundle\EventListener\SimpleTokenListener');
            }
        }
    }
}