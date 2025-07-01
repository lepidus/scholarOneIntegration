<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use DOMDocument;
use APP\submission\Submission;
use PKP\db\DAORegistry;

class GoXmlBuilder
{
    private $clientKey;
    private $journalShortName;

    public function __construct(string $clientKey, string $journalShortName)
    {
        $this->clientKey = $clientKey;
        $this->journalShortName = $journalShortName;
    }

    public function createGoXml(string $filePath, string $metadataXmlPath, array $files): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $docType = $dom->implementation->createDocumentType('GO', '', 'S1_GO.dtd');
        $dom->appendChild($docType);

        $goNode = $dom->createElement('GO');
        $dom->appendChild($goNode);

        $headerNode = $dom->createElement('header');
        $goNode->appendChild($headerNode);

        $clientKeyNode = $dom->createElement('clientkey', $this->clientKey);
        $headerNode->appendChild($clientKeyNode);
        $journalAbbreviationNode = $dom->createElement('journal_abbreviation', $this->journalShortName);
        $headerNode->appendChild($journalAbbreviationNode);

        $packageNode = $dom->createElement('package');
        $goNode->appendChild($packageNode);

        $splitXmlPath = explode('/', $metadataXmlPath);
        $metadataFileNameNode = $dom->createElement('metadata-file-name', end($splitXmlPath));
        $packageNode->appendChild($metadataFileNameNode);

        foreach ($files as $fileName) {
            $fileNode = $dom->createElement('file-name');
            $packageNode->appendChild($fileNode);
            $nameNode = $dom->createElement('name', $fileName);
            $fileNode->appendChild($nameNode);
        }

        $dom->save($filePath);
    }
}
