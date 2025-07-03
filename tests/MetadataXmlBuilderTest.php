<?php

use DOMDocument;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\scholarOneIntegration\classes\MetadataXmlBuilder;
use APP\plugins\generic\scholarOneIntegration\tests\helpers\SubmissionTestTrait;

class MetadataXmlBuilderTest extends DatabaseTestCase
{
    use SubmissionTestTrait;

    private $metadataXmlBuilder;
    private $submission;
    private $xmlPath = '/tmp/scholarone_test_metadata.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62';
    private $journalShortName = 'lepiduspreprints';
    private $files = [
        'main_document.pdf'
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

        $this->deleteSubmissionKeywords();

        if (file_exists($this->xmlPath)) {
            unlink($this->xmlPath);
        }
    }

    public function testBuildsMetadataXml(): void
    {
        $metadataXmlBuilder = new MetadataXmlBuilder($this->clientKey, $this->journalShortName);
        $metadataXmlBuilder->createMetadataXml($this->submission, $this->files, $this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = new DOMDocument();
        $expectedXml->load(__DIR__ . '/fixtures/jats_metadata.xml');

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
