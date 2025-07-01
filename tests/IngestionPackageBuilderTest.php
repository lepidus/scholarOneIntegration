<?php

use PKP\tests\PKPTestCase;
use APP\plugins\generic\scholarOneIntegration\classes\IngestionPackageBuilder;

class IngestionPackageBuilderTest extends PKPTestCase
{
    public function testBuildsIngestionPackage(): void
    {
        $ingestionPackageBuilder = new IngestionPackageBuilder();
        $buildStatus = $ingestionPackageBuilder->buildIngestionPackage();
        $this->assertTrue($buildStatus);

        // Check if package directory exists

        // Check content of the package directory
        // Presence of the GO XML file
        // Presence of the .zip file
        // Check presence of the metadata XML file inside the zip
        // Check presence of all files inside the zip
    }
}
