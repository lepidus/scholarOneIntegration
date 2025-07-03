<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use PKP\config\Config;
use APP\submission\Submission;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;
use APP\plugins\generic\scholarOneIntegration\classes\GoXmlBuilder;

class IngestionPackageBuilder
{
    public const PACKAGE_DIR_SUFFIX = '/tmp/ingestion_package_submission_';
    public const ARCHIVE_FILE_NAME = 'archive_file.zip';
    public const GO_XML_NAME = 'go.xml';
    public const METADATA_XML_NAME = 'metadata.xml';

    private $clientKey;
    private $journalShortName;
    private $submission;
    private $galleys;

    public function __construct(string $clientKey, string $journalShortName)
    {
        $this->clientKey = $clientKey;
        $this->journalShortName = $journalShortName;
    }

    public function setSubmission(Submission $submission): void
    {
        $this->submission = $submission;
    }

    public function setGalleys(array $galleys): void
    {
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

        $extractedFiles = $this->extractsGalleysFiles();
        if ($extractedFiles === false) {
            return false;
        }

        $metadataXmlPath = $packageDir . DIRECTORY_SEPARATOR . self::METADATA_XML_NAME;
        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($this->submission, $extractedFiles, $metadataXmlPath);

        $this->createArchiveFileZip($metadataXmlPath, $extractedFiles);
        $this->cleanMetadataXmlAndExtractedFiles($metadataXmlPath, $extractedFiles);

        $archiveFilePath = $packageDir . DIRECTORY_SEPARATOR . self::ARCHIVE_FILE_NAME;
        $goXmlPath = $packageDir . DIRECTORY_SEPARATOR . self::GO_XML_NAME;
        $goXmlBuilder = new GoXmlBuilder($this->clientKey, $this->journalShortName);
        $goXmlBuilder->createGoXml($goXmlPath, self::ARCHIVE_FILE_NAME, $metadataXmlPath, $extractedFiles);

        return true;
    }

    private function createArchiveFileZip(string $metadataXmlPath, array $extractedFiles)
    {
        $packageDir = $this->getPackageDir();
        $archiveFilePath = $packageDir . DIRECTORY_SEPARATOR . self::ARCHIVE_FILE_NAME;

        $zip = new \ZipArchive();
        $zip->open($archiveFilePath, \ZipArchive::CREATE);
        $zip->addFile($metadataXmlPath, self::METADATA_XML_NAME);

        foreach ($extractedFiles as $fileName) {
            $filePath = $packageDir . DIRECTORY_SEPARATOR . $fileName;
            $zip->addFile($filePath, $fileName);
        }
        $zip->close();
    }

    private function cleanMetadataXmlAndExtractedFiles(string $metadataXmlPath, array $extractedFiles)
    {
        $packageDir = $this->getPackageDir();
        unlink($metadataXmlPath);
        foreach ($extractedFiles as $fileName) {
            unlink($packageDir . DIRECTORY_SEPARATOR . $fileName);
        }
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
            $fileName = $submissionFile->getLocalizedData('name', $galley->getData('locale'));
            $pathToExtract = $packageDir . DIRECTORY_SEPARATOR . $fileName;
            $submissionFilePath = Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . $submissionFile->getData('path');

            if (!file_exists($submissionFilePath)) {
                error_log('ScholarOne Integration - File not found: ' . $submissionFilePath);
                return false;
            }

            $filesToExtract[] = ['from' => $submissionFilePath, 'to' => $pathToExtract, 'name' => $fileName];
        }

        $extractedFiles = [];
        foreach ($filesToExtract as $file) {
            copy($file['from'], $file['to']);
            $extractedFiles[] = $file['name'];
        }

        return $extractedFiles;
    }
}
