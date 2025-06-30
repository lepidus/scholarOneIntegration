<?php

use DOMDocument;
use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use PKP\galley\Galley;
use PKP\submissionFile\SubmissionFile;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;

class MetadataXmlBuilderTest extends DatabaseTestCase
{
    private $metadataXmlBuilder;
    private $submission;
    private $xmlPath = '/tmp/scholarone_test_metadata.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62';
    private $journalShortName = 'lepiduspreprints';
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
        $this->createSubmissionKeywords();
        $this->galley = $this->createSubmissionGalley();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        $publication = $this->submission->getCurrentPublication();
        $submissionKeywordDao->deleteByPublicationId($publication->getId());

        if (file_exists($this->xmlPath)) {
            unlink($this->xmlPath);
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
                'en' => "example_galley.pdf",
                'pt_BR' => "exemplo_galley.pdf"
            ],
            'locale' => $this->locale,
            'path' => 'contexts/1/files/1234/129nd092.pdf',
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

    public function testBuildsMetadataXml(): void
    {
        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($this->submission, [$this->galley], $this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = new DOMDocument();
        $expectedXml->load(__DIR__ . '/fixtures/jats_metadata.xml');

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
