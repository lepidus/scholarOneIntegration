<?php

/**
 * @file plugins/generic/scholarOneIntegration/ScholarOneIntegrationPlugin.php
 *
 * @class ScholarOneIntegrationPlugin
 * @ingroup plugins_generic_scholarOneIntegration
 *
 * @brief Plugin class for the ScholarOne Integration plugin.
 */

namespace APP\plugins\generic\scholarOneIntegration;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use PKP\plugins\Hook;
use APP\facades\Repo;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use APP\notification\NotificationManager;
use APP\plugins\generic\scholarOneIntegration\classes\APIKeyEncryption;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;
use APP\plugins\generic\scholarOneIntegration\ScholarOneIntegrationSettingsForm;

class ScholarOneIntegrationPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return true;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('Publication::publish', [$this, 'ingestSubmissionOnPosting']);
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.scholarOneIntegration.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.scholarOneIntegration.description');
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $contextId = ($context == null) ? 0 : $context->getId();

                $form = new ScholarOneIntegrationSettingsForm($this, $contextId);
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        $notificationManager = new NotificationManager();
                        $notificationManager->createTrivialNotification($request->getUser()->getId());
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }

                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function ingestSubmissionOnPosting($hookName, $params)
    {
        $publication = $params[0];
        $submission = $params[2];
        $contextId = $submission->getContextId();

        if ($publication->getData('version') > 1) {
            return;
        }

        $clientKey = $this->getSetting($contextId, 'clientKey');
        $journalShortName = $this->getSetting($contextId, 'journalShortName');
        if (empty($clientKey) || empty($journalShortName)) {
            return;
        }

        $clientKey = APIKeyEncryption::decryptString($clientKey);
        $galleys = Repo::galley()->getCollector()
            ->filterByPublicationIds(['publicationIds' => $publication->getId()])
            ->getMany()
            ->toArray();

        $ingestionPackageBuilder = new IngestionPackageBuilder($clientKey, $journalShortName);
        $ingestionPackageBuilder->setSubmission($submission);
        $ingestionPackageBuilder->setGalleys($galleys);
        $ingestionPackageBuilder->buildIngestionPackage();
    }
}
