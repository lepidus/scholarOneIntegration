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

class IngestionPackageBuilderTest extends DatabaseTestCase
{
    private $clientKey = '59b4ca87-2c51-exemplo-4a62jd-04woci';
    private $journalShortName = 'lepiduspreprints';
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
        $this->createSubmissionKeywords();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        $publication = $this->submission->getCurrentPublication();
        $submissionKeywordDao->deleteByPublicationId($publication->getId());

        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();
        if (is_dir($packageDir)) {
            rmdir($packageDir);
        }
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

        $authors = [];
        foreach ($this->authors as $authorData) {
            $author = new Author();
            $author->setId($authorData['id']);
            $author->setData('givenName', $authorData['givenName']);
            $author->setData('familyName', $authorData['familyName']);
            $author->setData('email', $authorData['email']);
            if (isset($authorData['affiliation'])) {
                $author->setData('affiliation', $authorData['affiliation'], $this->locale);
            }

            $authors[] = $author;
        }

        $publication->setData('authors', $authors);
        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);

        return $submission;
    }

    private function createSubmissionKeywords()
    {
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        $publication = $this->submission->getCurrentPublication();
        $submissionKeywordDao->insertKeywords($this->keywords, $publication->getId(), false);
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

        $archiveFilePath = $packageDir . DIRECTORY_SEPARATOR . IngestionPackageBuilder::ARCHIVE_FILE_NAME;
        // $this->assertFileExists($packageDir . '/go.xml');
        $this->assertFileExists($archiveFilePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archiveFilePath));
        $this->assertEquals(2, $zip->numFiles);
        $this->assertNotFalse($zip->locateName(IngestionPackageBuilder::METADATA_XML_NAME));
        $this->assertNotFalse($zip->locateName('documento_principal_1234.pdf'));
        $zip->close();

        unlink($archiveFilePath);
    }
}
