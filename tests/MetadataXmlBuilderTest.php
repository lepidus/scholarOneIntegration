<?php

use PKP\tests\PKPTestCase;
use DOMDocument;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;

class MetadataXmlBuilderTest extends PKPTestCase
{
    private $metadataXmlBuilder;
    private $xmlPath = '/tmp/scholarone_test_metadata.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62';
    private $journalShortName = 'lepiduspreprints';

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
        return $dom->createElement('article-meta');
    }

    public function testBuildsMetadataXml(): void
    {
        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = $this->createExpectedXml();

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
