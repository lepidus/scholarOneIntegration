<?php

use ZipArchive;
use PKP\tests\DatabaseTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use PKP\galley\Galley;
use PKP\submissionFile\SubmissionFile;
use APP\facades\Repo;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;
use APP\plugins\generic\scholarOneIntegration\tests\helpers\SubmissionTestTrait;

class IngestionPackageBuilderTest extends DatabaseTestCase
{
    use SubmissionTestTrait;

    private $clientKey = '59b4ca87-2c51-exemplo-4a62jd-04woci';
    private $journalShortName = 'lepiduspreprints';
    private $submission;
    private $galley;

    public function setUp(): void
    {
        parent::setUp();
        $this->submission = $this->createSubmission();
        $this->galley = $this->createSubmissionGalley();
        $this->createSubmissionKeywords();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->deleteSubmissionKeywords();

        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();
        if (is_dir($packageDir)) {
            rmdir($packageDir);
        }
    }

    private function createSubmissionGalley()
    {
        $submissionFile = new SubmissionFile();
        $submissionFile->setAllData([
            'id' => 4285,
            'submissionId' => $this->submission->getId(),
            'name' => [
                'en' => 'main_document_' . $this->submission->getId() . '.pdf',
                'pt_BR' => 'documento_principal_' . $this->submission->getId() . '.pdf'
            ],
            'locale' => $this->locale,
            'path' => '../plugins/generic/scholarOneIntegration/tests/fixtures/dummy.pdf',
            'mimeType' => 'application/pdf'
        ]);

        $galley = new Galley();
        $galley->setAllData([
            'id' => 1712,
            'publicationId' => $this->submission->getCurrentPublication()->getId(),
            'submissionFileId' => $submissionFile->getId(),
            'locale' => $this->locale,
            'label' => 'PDF'
        ]);
        $galley->_submissionFile = $submissionFile;

        return $galley;
    }

    public function testExtractsGalleysFiles(): void
    {
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();
        mkdir($packageDir);

        $ingestionPackageBuilder = new IngestionPackageBuilder($this->clientKey, $this->journalShortName);
        $ingestionPackageBuilder->setSubmission($this->submission);
        $ingestionPackageBuilder->setGalleys([$this->galley]);
        $extractedFiles = $ingestionPackageBuilder->extractsGalleysFiles([$this->galley]);

        $submissionFile = $this->galley->getFile();
        $expectedFilePath = $packageDir . DIRECTORY_SEPARATOR . $submissionFile->getData('name', $this->locale);

        $this->assertEquals($expectedFilePath, $packageDir . DIRECTORY_SEPARATOR . $extractedFiles[0]);
        $this->assertFileExists($expectedFilePath);
        $this->assertFileEquals(__DIR__.'/fixtures/dummy.pdf', $expectedFilePath);

        unlink($expectedFilePath);
    }

    public function testGalleysExtractionWithNoGalleys(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->clientKey, $this->journalShortName);
        $ingestionPackageBuilder->setGalleys([]);
        $extractedFiles = $ingestionPackageBuilder->extractsGalleysFiles();

        $this->assertFalse($extractedFiles);
    }

    public function testBuildsIngestionPackage(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->clientKey, $this->journalShortName);
        $ingestionPackageBuilder->setSubmission($this->submission);
        $ingestionPackageBuilder->setGalleys([$this->galley]);
        $buildStatus = $ingestionPackageBuilder->buildIngestionPackage();
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();

        $this->assertTrue($buildStatus);
        $this->assertDirectoryExists($packageDir);

        $goFilePath = $packageDir . DIRECTORY_SEPARATOR . IngestionPackageBuilder::GO_XML_NAME;
        $archiveFilePath = $packageDir . DIRECTORY_SEPARATOR . IngestionPackageBuilder::ARCHIVE_FILE_NAME;
        $this->assertFileExists($goFilePath);
        $this->assertFileExists($archiveFilePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archiveFilePath));
        $this->assertEquals(2, $zip->numFiles);
        $this->assertNotFalse($zip->locateName(IngestionPackageBuilder::METADATA_XML_NAME));
        $this->assertNotFalse($zip->locateName('documento_principal_1234.pdf'));
        $zip->close();

        unlink($goFilePath);
        unlink($archiveFilePath);
    }

    public function testCleansUpPackageDirectory(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->clientKey, $this->journalShortName);
        $ingestionPackageBuilder->setSubmission($this->submission);
        $ingestionPackageBuilder->setGalleys([$this->galley]);
        $buildStatus = $ingestionPackageBuilder->buildIngestionPackage();
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();

        $this->assertTrue($buildStatus);
        $this->assertDirectoryExists($packageDir);

        $ingestionPackageBuilder->cleanPackageDirectory();
        $this->assertDirectoryDoesNotExist($packageDir);
    }
}
