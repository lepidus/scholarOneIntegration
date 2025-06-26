<?php

use DOMDocument;
use PKP\tests\DatabaseTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;

class MetadataXmlBuilderTest extends DatabaseTestCase
{
    private $metadataXmlBuilder;
    private $submission;
    private $xmlPath = '/tmp/scholarone_test_metadata.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62';
    private $journalShortName = 'lepiduspreprints';
    private $locale = 'pt_BR';
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
            'givenName' => 'John',
            'familyName' => 'Doe',
            'email' => 'john.doe@example.com',
            'affiliation' => 'University of Example'
        ],
        [
            'givenName' => 'Jane',
            'familyName' => 'Smith',
            'email' => 'jane.smith@example.com'
        ],
        [
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

    private function createExpectedXml()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $articleNode = $dom->createElement('article');
        $dom->appendChild($articleNode);

        $articleNode->setAttribute('xmlns:oasis', 'http://www.niso.org/standards/z39-96/ns/oasis-exchange/table');
        $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $frontNode = $dom->createElement('front');
        $articleNode->appendChild($frontNode);

        $journalMetaNode = $this->createJournalMetaNode($dom);
        $frontNode->appendChild($journalMetaNode);

        $articleMeta = $this->createArticleMetaNode($dom);
        $frontNode->appendChild($articleMeta);

        return $dom;
    }

    private function createJournalMetaNode($dom)
    {
        $journalMetaNode = $dom->createElement('journal-meta');

        $journalIdNode = $dom->createElement('journal-id');
        $journalIdNode->setAttribute('journal-id-type', 'publisher');
        $journalIdNode->appendChild($dom->createTextNode($this->clientKey));

        $journalMetaNode->appendChild($journalIdNode);

        $journalTitleGroupNode = $dom->createElement('journal-title-group');
        $journalTitleNode = $dom->createElement('journal-title');
        $journalTitleNode->appendChild($dom->createTextNode($this->journalShortName));
        $journalTitleGroupNode->appendChild($journalTitleNode);

        $journalMetaNode->appendChild($journalTitleGroupNode);

        return $journalMetaNode;
    }

    private function createArticleMetaNode($dom)
    {
        $articleMetaNode = $dom->createElement('article-meta');

        $titleGroupNode = $dom->createElement('title-group');
        $articleTitleNode = $dom->createElement('article-title');
        $articleTitleNode->appendChild($dom->createTextNode($this->title['pt_BR']));
        $titleGroupNode->appendChild($articleTitleNode);
        $articleMetaNode->appendChild($titleGroupNode);

        $abstractNode = $dom->createElement('abstract');
        $paragraph = $dom->createElement('p');
        $paragraph->appendChild($dom->createTextNode($this->abstract['pt_BR']));
        $abstractNode->appendChild($paragraph);
        $articleMetaNode->appendChild($abstractNode);

        $keywordsNode = $dom->createElement('kwd-group');
        $keywordsNode->setAttribute('kwd-group-type', 'Keywords');
        $keywordsNode->setAttribute('id', '');
        foreach ($this->keywords['pt_BR'] as $keyword) {
            $keywordNode = $dom->createElement('kwd');
            $keywordNode->setAttribute('id', '');
            $keywordNode->appendChild($dom->createTextNode($keyword));

            $keywordsNode->appendChild($keywordNode);
        }
        $articleMetaNode->appendChild($keywordsNode);

        $contributorGroupNode = $dom->createElement('contrib-group');
        $affiliations = [];
        foreach ($this->authors as $authorData) {
            $contributorNode = $dom->createElement('contrib');
            $contributorNode->setAttribute('contrib-type', 'author');

            $nameNode = $dom->createElement('name');
            $givenNamesNode = $dom->createElement('given-names', $authorData['givenName']);
            $surnameNode = $dom->createElement('surname', $authorData['familyName']);
            $nameNode->appendChild($givenNamesNode);
            $nameNode->appendChild($surnameNode);
            $contributorNode->appendChild($nameNode);
    
            $emailNode = $dom->createElement('email', $authorData['email']);
            $contributorNode->appendChild($emailNode);

            if (isset($authorData['affiliation'])) {
                $affiliations[] = $authorData['affiliation'];
                $xrefNode = $dom->createElement('xref');
                $xrefNode->setAttribute('ref-type', 'aff');
                $xrefNode->setAttribute('rid', 'aff' . count($affiliations));
                $contributorNode->appendChild($xrefNode);
            }

            $contributorGroupNode->appendChild($contributorNode);
        }

        foreach($affiliations as $index => $affiliation) {
            $affNode = $dom->createElement('aff');
            $affNode->setAttribute('id', 'aff' . ($index + 1));

            $institutionNode = $dom->createElement('institution');
            $institutionNode->appendChild($dom->createTextNode($affiliation));
            $affNode->appendChild($institutionNode);
            $contributorGroupNode->appendChild($affNode);
        }

        $articleMetaNode->appendChild($contributorGroupNode);

        return $articleMetaNode;
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
            'abstract' => $this->abstract
        ]);

        $authors = [];
        foreach ($this->authors as $authorData) {
            $author = new Author();
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

    public function testBuildsMetadataXml(): void
    {
        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($this->submission, $this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = $this->createExpectedXml();

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
