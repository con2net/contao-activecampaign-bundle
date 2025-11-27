<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/EventListener/InsertTagListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

/**
 * Insert-Tag Hook für ActiveCampaign Transfer-Link
 * Unterstützt: {{activecampaign_transfer}}
 */
class InsertTagListener
{
    /**
     * Hook: replaceInsertTags
     */
    public function __invoke(string $tag): string|false
    {
        $chunks = explode('::', $tag);

        // Nur unsere Tags behandeln
        if ($chunks[0] !== 'activecampaign_transfer') {
            return false;
        }

        // Link aus GLOBALS holen (wird vom FormSubmitListener gesetzt)
        $transferLink = $GLOBALS['ACTIVECAMPAIGN_TRANSFER_LINK'] ?? '';

        if (!$transferLink) {
            return '';
        }

        // Format: {{activecampaign_transfer}} oder {{activecampaign_transfer::label}}
        if (isset($chunks[1])) {
            // Mit Label: <a href="...">Label</a>
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                $transferLink,
                $chunks[1]
            );
        }

        // Ohne Label: Nur URL
        return $transferLink;
    }
}
