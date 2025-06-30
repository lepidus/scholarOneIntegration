<?php

use DOMDocument;
use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use PKP\galley\Galley;
use PKP\submissionFile\SubmissionFile;
use APP\plugins\generic\scholarOneIntegration\classes\GoXmlBuilder;

class GoXmlBuilderTest extends DatabaseTestCase
{
    private $goXmlBuilder;
    private $xmlPath = '/tmp/scholarone_test_go.xml';
    private $clientKey = '59b4ca87-2c51-exemplo-4a62jd-04woci';
    private $journalShortName = 'lepiduspreprints';

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->xmlPath)) {
            unlink($this->xmlPath);
        }
    }

    public function testBuildsGoXml(): void
    {
        $goXmlBuilder = new GoXmlBuilder($this->clientKey, $this->journalShortName);
        $goXmlBuilder->createGoXml($this->xmlPath);
        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $expectedXml = new DOMDocument();
        $expectedXml->load(__DIR__ . '/fixtures/go.xml');

        $this->assertEquals($expectedXml->saveXML(), $writtenXml->saveXML());
    }
}
