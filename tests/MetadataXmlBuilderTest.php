<?php

use PKP\tests\PKPTestCase;
use DOMDocument;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;

class MetadataXmlBuilderTest extends PKPTestCase
{
    private $metadataXmlBuilder;
    private $xmlPath = '/tmp/scholarone_test_metadata.xml';

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
        return $dom->createElement('journal-meta');
    }

    private function createArticleMetaNode($dom)
    {
        return $dom->createElement('article-meta');
    }

    public function testBuildsMetadataXml(): void
    {
        $metadataXmlBuilder = new MetadataXmlBuilder();
        $metadataXmlBuilder->createMetadataXml($this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = $this->createExpectedXml();

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
