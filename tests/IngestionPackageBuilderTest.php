<?php

use ZipArchive;
use PKP\tests\PKPTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use PKP\galley\Galley;
use PKP\submissionFile\SubmissionFile;
use APP\facades\Repo;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;

class IngestionPackageBuilderTest extends PKPTestCase
{
    private $submission;
    private $locale = 'pt_BR';
    private $galley;
    private $doi = '10.1234/LepidusPreprints.5678';
    private $title = [
        'en' => 'Sad songs about love',
        'pt_BR' => 'Músicas tristes sobre amor'
    ];
    private $abstract = [
        'en' => 'Example of abstract',
        'pt_BR' => 'Exemplo de resumo'
    ];
    private $keywords = [
        'en' => ['song', 'love'],
        'pt_BR' => ['música', 'amor']
    ];
    private $authors = [
        [
            'id' => 1,
            'givenName' => 'John',
            'familyName' => 'Doe',
            'email' => 'john.doe@example.com',
            'affiliation' => 'University of Example'
        ],
        [
            'id' => 2,
            'givenName' => 'Jane',
            'familyName' => 'Smith',
            'email' => 'jane.smith@example.com'
        ],
        [
            'id' => 3,
            'givenName' => 'Alice',
            'familyName' => 'Johnson',
            'email' => 'alice.johnson@example.com',
            'affiliation' => 'Example University'
        ]
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->submission = $this->createSubmission();
        $this->galley = $this->createSubmissionGalley();
        // criar keywords
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();
        if (is_dir($packageDir)) {
            rmdir($packageDir);
        }

        // excluir keywords
    }

    private function createSubmission()
    {
        $submission = new Submission();
        $submission->setAllData([
            'id' => 1234,
            'locale' => $this->locale
        ]);

        $publication = new Publication();
        $publication->setAllData([
            'id' => 1245,
            'title' => $this->title,
            'abstract' => $this->abstract,
            'primaryContactId' => 1,
            'doiObject' =>  Repo::doi()->newDataObject([
                'doi' => $this->doi
            ])
        ]);

        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);

        return $submission;
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

        $ingestionPackageBuilder = new IngestionPackageBuilder($this->submission, [$this->galley]);
        $extractedFiles = $ingestionPackageBuilder->extractsGalleysFiles();

        $submissionFile = $this->galley->getFile();
        $expectedFilePath = $packageDir . '/' . $submissionFile->getData('name', $this->locale);

        $this->assertEquals($expectedFilePath, $extractedFiles[0]);
        $this->assertFileExists($expectedFilePath);
        $this->assertFileEquals(__DIR__.'/fixtures/dummy.pdf', $expectedFilePath);

        unlink($expectedFilePath);
    }

    public function testGalleysExtractionWithNoGalleys(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->submission);
        $extractedFiles = $ingestionPackageBuilder->extractsGalleysFiles();

        $this->assertFalse($extractedFiles);
    }

    public function testBuildsIngestionPackage(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->submission);
        $buildStatus = $ingestionPackageBuilder->buildIngestionPackage();
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();

        $this->assertTrue($buildStatus);
        $this->assertDirectoryExists($packageDir);

        $archiveFilePath = $packageDir . '/archive_file.zip';
        // $this->assertFileExists($packageDir . '/go.xml');
        $this->assertFileExists($archiveFilePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archiveFilePath));
        $this->assertEquals(2, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('metadata.xml'));
        $this->assertNotFalse($zip->locateName('documento_principal_1234.pdf'));
        $zip->close();
    }
}
