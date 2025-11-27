<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/EventListener/SimpleTokenListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Terminal42\NotificationCenterBundle\Event\CreateParserEvent;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

/**
 * Listener für Notification Center Simple Tokens
 * Stellt den Token ##activecampaign_transfer_link## bereit
 *
 * Kompatibel mit NC 1.x und 2.x durch doppelten Fallback
 */
class SimpleTokenListener
{
    public function __construct(
        private readonly TokenDefinitionFactoryInterface $factory
    ) {
    }

    /**
     * Registriert den Transfer-Link Token
     */
    public function __invoke(CreateParserEvent $event): void
    {
        $parser = $event->getParser();

        // Token nur für 'contao_form' Notification Type
        if ($parser->getLanguage()->getNotificationType()->getType() !== 'contao_form') {
            return;
        }

        // Token Definition erstellen
        $definition = new TextTokenDefinition(
            'activecampaign_transfer_link',
            'ActiveCampaign Transfer-Link (nur bei Delayed Transfer)'
        );

        // Token registrieren
        $parser->addTokenDefinition($definition);

        // Wert aus GLOBALS holen (Fallback auf beide Keys)
        // Priorität: ACTIVECAMPAIGN_TRANSFER_LINK (Primary) -> TL_SIMPLE_TOKENS (Fallback)
        $transferLink = $GLOBALS['ACTIVECAMPAIGN_TRANSFER_LINK']
            ?? $GLOBALS['TL_SIMPLE_TOKENS']['activecampaign_transfer_link']
            ?? '';

        if ($transferLink) {
            $parser->addToken('activecampaign_transfer_link', $transferLink);
        }
    }
}