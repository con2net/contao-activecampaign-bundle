<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Controller/ContentElement/ActiveCampaignFormElementController.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\FormModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Element Controller fÃ¼r ActiveCampaign Formulare
 */
class ActiveCampaignFormElementController extends AbstractContentElementController
{
    /**
     * {@inheritdoc}
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        // Formular laden
        $formModel = FormModel::findByPk($model->c2n_ac_form_id);

        if (!$formModel) {
            $template->noForm = true;
            $template->formId = 0;
            return $template->getResponse();
        }

        // ActiveCampaign-Konfiguration speichern
        $GLOBALS['ACTIVECAMPAIGN_FORMS'][$formModel->id] = [
            'list_id' => $model->c2n_ac_list_id,
            'tags' => $model->c2n_ac_tags ? array_map('trim', explode(',', $model->c2n_ac_tags)) : [],
            'debug' => false, // Debug aus Content Element entfernt
            'delayed_transfer' => (bool)$model->c2n_ac_delay_transfer,
            'auto_delete_days' => (int)($model->c2n_ac_auto_delete_days ?: 10)
        ];

        // Timestamp in SESSION speichern (falls Anti-SPAM Bundle aktiv ist)
        // WICHTIG: $request->getSession() funktioniert in Contao 4.13 UND 5.x
        if ($request->hasSession()) {
            $session = $request->getSession();
            $sessionKey = 'c2n_form_timestamp_' . $formModel->id;

            if (!$session->has($sessionKey)) {
                $timestamp = time();
                $session->set($sessionKey, $timestamp);
            }
        }

        // Formular rendern
        $formHtml = Controller::getForm($formModel->id);

        $template->form = $formHtml;
        $template->formId = $formModel->id;
        $template->noForm = false;

        return $template->getResponse();
    }
}