<?php

use PKP\tests\PKPTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\facades\Repo;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;

class IngestionPackageBuilderTest extends PKPTestCase
{
    private $submission;
    private $locale = 'pt_BR';
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
    }

    public function tearDown(): void
    {
        parent::tearDown();
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

        return $submission;
    }

    public function testBuildsIngestionPackage(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder($this->submission);
        $buildStatus = $ingestionPackageBuilder->buildIngestionPackage();
        $packageDir = IngestionPackageBuilder::PACKAGE_DIR_SUFFIX.$this->submission->getId();


        $this->assertTrue($buildStatus);
        $this->assertDirectoryExists($packageDir);

        // $this->assertFileExists($packageDir . '/go.xml');
        // $this->assertFileExists($packageDir . '/archive_file.zip');

        // Check presence of the metadata XML file inside the zip
        // Check presence of all files inside the zip
    }
}
