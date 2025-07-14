<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

require __DIR__ . '/../vendor/autoload.php';

class ScholarOneS3Client
{
    private const BUCKET_NAME_DEV = 'clarivate-scholarone-dev-us-west-2-s1m-submission';
    private const BUCKET_NAME_PROD = 'clarivate-scholarone-prod-us-west-2-s1m-submission';
    private const REGION = 'us-west-2';

    private $s3Client;
    private $accessKey;
    private $secretKey;
    private $stack;
    private $clientKey;
    private $developmentMode = false;

    public function __construct(string $accessKey, string $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;

        $this->s3Client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => self::REGION,
            'credentials' => new \Aws\Credentials\Credentials(
                $this->accessKey,
                $this->secretKey
            )
        ]);
    }

    public function setS3Client($s3Client)
    {
        $this->s3Client = $s3Client;
    }

    public function setStack($stack)
    {
        $this->stack = $stack;
    }

    public function setClientKey($clientKey)
    {
        $this->clientKey = $clientKey;
    }

    public function setDevelopmentMode($developmentMode)
    {
        $this->developmentMode = $developmentMode;
    }

    public function depositFile($filePath)
    {
        $fileName = basename($filePath);
        $bucketName = $this->developmentMode ? self::BUCKET_NAME_DEV : self::BUCKET_NAME_PROD;
        $result = $this->s3Client->putObject([
            'Bucket' => $bucketName,
            'Key' => $this->stack . '/incoming/' . $this->clientKey . '/' . $fileName,
            'SourceFile' => $filePath,
        ]);

        return [
            'statusCode' => $result['@metadata']['statusCode'],
            'objectUrl' => $result['ObjectURL'] ?? null
        ];
    }
}
