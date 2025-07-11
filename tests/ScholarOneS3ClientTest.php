<?php

use PHPUnit\Framework\TestCase;
use APP\plugins\generic\scholarOneIntegration\classes\ScholarOneS3Client;

require __DIR__ . '/../vendor/autoload.php';

class ScholarOneS3ClientTest extends TestCase
{
    private $testAccessKey = 'test-access-key';
    private $testSecretKey = 'test-secret-key';
    private $scholarOneStack = 'impli1';
    private $scholarOneClientKey = 'test-client-key';

    public function setUp(): void
    {
        parent::setUp();
    }

    private function createScholarOneS3Client($fileName)
    {
        $s3Client = new ScholarOneS3Client();
        $mockS3Client = $this->getS3ClientMock($fileName);
        $s3Client->setS3Client($mockS3Client);
        $s3Client->setCredentials($this->testAccessKey, $this->testSecretKey);
        $s3Client->setStack($this->scholarOneStack);
        $s3Client->setClientKey($this->scholarOneClientKey);
        $s3Client->setDevelopmentMode(true);

        return $s3Client;
    }

    private function getS3ClientMock($fileName)
    {
        $mockS3Client = $this->getMockBuilder(\Aws\S3\S3Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['putObject'])
            ->getMock();

        $response = new \Aws\Result([
            'ObjectURL' => 'http://s3-us-west-2.amazonaws.com/clarivate-scholarone-dev-us-west-2-s1m-submission/' .
                $this->scholarOneStack . '/incoming/' . $this->scholarOneClientKey . '/' . $fileName,
            '@metadata' => [
                'statusCode' => 200,
            ],
        ]);

        $mockS3Client->expects($this->any())
            ->method('putObject')
            ->will($this->returnValue($response));
    }

    public function testClientDepositsPackage()
    {
        $filePath = __DIR__ . '/fixtures/go.xml';
        $s3Client = $this->createScholarOneS3Client('go.xml');
        $depositResult = $s3Client->depositFile($filePath);

        $expectedObjectUrl = 'http://s3-us-west-2.amazonaws.com/clarivate-scholarone-dev-us-west-2-s1m-submission/';
        $expectedObjectUrl .= $this->scholarOneStack . '/incoming/' . $this->scholarOneClientKey . '/go.xml';
        $expectedDepositResult = [
            'statusCode' => 200,
            'objectUrl' => $expectedObjectUrl
        ];
        $this->assertEquals($expectedDepositResult, $depositResult);
    }
}
