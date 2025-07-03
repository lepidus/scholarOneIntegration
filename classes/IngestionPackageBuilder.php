<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use PKP\config\Config;
use APP\submission\Submission;

class IngestionPackageBuilder
{
    public const PACKAGE_DIR_SUFFIX = '/tmp/ingestion_package_submission_';

    private $submission;
    private $galleys;

    public function __construct(Submission $submission, array $galleys = [])
    {
        $this->submission = $submission;
        $this->galleys = $galleys;
    }

    private function getPackageDir(): string
    {
        return self::PACKAGE_DIR_SUFFIX . $this->submission->getId();
    }

    public function buildIngestionPackage(): bool
    {
        $packageDir = $this->getPackageDir();
        mkdir($packageDir);

        return true;
    }

    public function extractsGalleysFiles(): array|bool
    {
        if (empty($this->galleys)) {
            error_log('ScholarOne Integration - No galleys available for extraction.');
            return false;
        }

        $packageDir = $this->getPackageDir();
        $filesToExtract = [];

        foreach ($this->galleys as $galley) {
            $submissionFile = $galley->getFile();
            $pathToExtract = $packageDir . DIRECTORY_SEPARATOR . $submissionFile->getLocalizedData('name', $galley->getData('locale'));
            $submissionFilePath = Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . $submissionFile->getData('path');

            if (!file_exists($submissionFilePath)) {
                error_log('ScholarOne Integration - File not found: ' . $submissionFilePath);
                return false;
            }

            $filesToExtract[] = ['from' => $submissionFilePath, 'to' => $pathToExtract];
        }

        $extractedFiles = [];
        foreach ($filesToExtract as $file) {
            copy($file['from'], $file['to']);
            $extractedFiles[] = $file['to'];
        }

        return $extractedFiles;
    }
}
