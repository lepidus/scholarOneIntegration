<?php

use DOMDocument;
use PKP\tests\PKPTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;

class MetadataXmlBuilderTest extends PKPTestCase
{
    private $metadataXmlBuilder;
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

        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);

        return $submission;
    }

    public function testBuildsMetadataXml(): void
    {
        $submission = $this->createSubmission();

        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($submission, $this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = $this->createExpectedXml();

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
