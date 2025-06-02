<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use DOMDocument;

class MetadataXmlBuilder
{
    private $clientKey;
    private $journalShortName;

    public function __construct(string $clientKey, string $journalShortName)
    {
        $this->clientKey = $clientKey;
        $this->journalShortName = $journalShortName;
    }

    public function createMetadataXml(string $xmlFilePath): void
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

        $dom->save($xmlFilePath);
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
}
