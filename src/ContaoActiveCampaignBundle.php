<?php
// Pfad: vendor/con2net/contao-activecampaign-bundle/src/ContaoActiveCampaignBundle.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Haupt-Bundle-Klasse
 */
class ContaoActiveCampaignBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__;
    }
}