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
use PKP\core\Core;
use PKP\plugins\Hook;
use APP\facades\Repo;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use APP\notification\NotificationManager;
use APP\log\event\SubmissionEventLogEntry;
use APP\plugins\generic\scholarOneIntegration\classes\APIKeyEncryption;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;
use APP\plugins\generic\scholarOneIntegration\classes\schema\SchemaEditor;
use APP\plugins\generic\scholarOneIntegration\classes\ScholarOneS3Client;
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
            $this->editSchemas();
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

    private function editSchemas()
    {
        $schemaEditor = new SchemaEditor();
        Hook::add('Schema::add::eventLog', [$schemaEditor, 'editEventLogSchema']);
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
        $ingestionSettings = $this->getIngestionSettings($contextId);

        if ($publication->getData('version') > 1 || empty($ingestionSettings)) {
            return;
        }

        $galleys = Repo::galley()->getCollector()
            ->filterByPublicationIds(['publicationIds' => $publication->getId()])
            ->getMany()
            ->toArray();

        $ingestionPackageBuilder = new IngestionPackageBuilder(
            $ingestionSettings['clientKey'],
            $ingestionSettings['journalShortName']
        );
        $ingestionPackageBuilder->setSubmission($submission);
        $ingestionPackageBuilder->setGalleys($galleys);
        $packageBuildingStatus = $ingestionPackageBuilder->buildIngestionPackage();

        if (!$packageBuildingStatus) {
            return;
        }

        $packageDirectory = $ingestionPackageBuilder->getPackageDir();
        $goXmlFile = $packageDirectory . DIRECTORY_SEPARATOR . IngestionPackageBuilder::GO_XML_NAME;
        $archiveFile = $packageDirectory . DIRECTORY_SEPARATOR . IngestionPackageBuilder::ARCHIVE_FILE_NAME;

        $s3Client = new ScholarOneS3Client(
            $ingestionSettings['accessKey'],
            $ingestionSettings['secretKey']
        );
        $s3Client->setClientKey($ingestionSettings['clientKey']);

        $okStatus = 200;
        $depositStatusGoXml = $s3Client->depositFile($goXmlFile);
        if ($depositStatusGoXml['statusCode'] != $okStatus) {
            $this->writeToSubmissionEventLog(
                $submission,
                'plugins.generic.scholarOneIntegration.log.errorDepositingFile',
                [
                    'filename' => IngestionPackageBuilder::GO_XML_NAME,
                    'errorMessage' => $depositStatusGoXml['errorMessage']
                ]
            );
            $ingestionPackageBuilder->cleanPackageDirectory();
            return;
        }

        $depositStatusArchive = $s3Client->depositFile($archiveFile);
        if ($depositStatusArchive['statusCode'] != $okStatus) {
            $this->writeToSubmissionEventLog(
                $submission,
                'plugins.generic.scholarOneIntegration.log.errorDepositingFile',
                [
                    'filename' => IngestionPackageBuilder::ARCHIVE_FILE_NAME,
                    'errorMessage' => $depositStatusArchive['errorMessage']
                ]
            );
            $ingestionPackageBuilder->cleanPackageDirectory();
            return;
        }

        $this->writeToSubmissionEventLog(
            $submission,
            'plugins.generic.scholarOneIntegration.log.successfulDeposit'
        );
        $ingestionPackageBuilder->cleanPackageDirectory();
    }

    private function getIngestionSettings(int $contextId): array
    {
        $clientKey = $this->getSetting($contextId, 'clientKey');
        $accessKey = $this->getSetting($contextId, 'accessKey');
        $secretKey = $this->getSetting($contextId, 'secretKey');

        if (empty($clientKey) || empty($accessKey) || empty($secretKey)) {
            return [];
        }

        return [
            'journalShortName' => $this->getSetting($contextId, 'journalShortName'),
            'clientKey' =>  APIKeyEncryption::decryptString($clientKey),
            'accessKey' =>  APIKeyEncryption::decryptString($accessKey),
            'secretKey' =>  APIKeyEncryption::decryptString($secretKey)
        ];
    }

    private function writeToSubmissionEventLog($submission, $messageKey, $params = [])
    {
        $user = Application::get()->getRequest()->getUser();

        $eventLogData = array_merge([
            'assocType' => Application::ASSOC_TYPE_SUBMISSION,
            'assocId' => $submission->getId(),
            'userId' => $user->getId(),
            'eventType' => SubmissionEventLogEntry::SUBMISSION_LOG_METADATA_UPDATE,
            'message' => $messageKey,
            'dateLogged' => Core::getCurrentDate(),
        ], $params);

        $eventLog = Repo::eventLog()->newDataObject($eventLogData);
        Repo::eventLog()->add($eventLog);
    }
}
