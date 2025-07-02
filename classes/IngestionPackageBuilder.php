<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use APP\submission\Submission;

class IngestionPackageBuilder
{
    public const PACKAGE_DIR_SUFFIX = '/tmp/ingestion_package_submission_';

    private $submission;

    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    public function buildIngestionPackage(): bool
    {
        $packageDir = self::PACKAGE_DIR_SUFFIX . $this->submission->getId();
        mkdir($packageDir);

        return true;
    }
}
