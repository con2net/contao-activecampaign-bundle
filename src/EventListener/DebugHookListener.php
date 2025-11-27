<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/EventListener/DebugHookListener.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\EventListener;

use Contao\Form;

class DebugHookListener
{
    public function __invoke(
        array $submittedData,
        array $labels,
        array $fields,
        Form $form
    ): array {
        // Direkt in Error-Log schreiben
        error_log('==========================================');
        error_log('🔴 DEBUG HOOK CALLED! Form ID: ' . $form->id);
        error_log('==========================================');

        return $submittedData;
    }
}