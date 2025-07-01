<?php

use DOMDocument;
use PKP\tests\PKPTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use PKP\galley\Galley;
use PKP\submissionFile\SubmissionFile;
use APP\plugins\generic\scholarOneIntegration\classes\GoXmlBuilder;

class GoXmlBuilderTest extends PKPTestCase
{
    private $goXmlBuilder;
    private $goXmlPath = '/tmp/scholarone_test_go.xml';
    private $packageFileName = 'scholarone_ingestion_package.zip';
    private $metadataXmlPath = '/tmp/scholarone_test_metadata.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62jd-04woci';
    private $journalShortName = 'lepiduspreprints';
    private $files = [
        'main_document.pdf'
    ];

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->goXmlPath)) {
            unlink($this->goXmlPath);
        }
    }

    public function testBuildsGoXml(): void
    {
        $goXmlBuilder = new GoXmlBuilder($this->clientKey, $this->journalShortName);
        $goXmlBuilder->createGoXml($this->goXmlPath, $this->packageFileName, $this->metadataXmlPath, $this->files);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->goXmlPath);

        $expectedXml = new DOMDocument();
        $expectedXml->load(__DIR__ . '/fixtures/go.xml');

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
