<?php

/**
 * @file plugins/generic/scholarOneIntegration/ScholarOneIntegrationPlugin.php
 *
 * @class ScholarOneIntegrationPlugin
 * @ingroup plugins_generic_scholarOneIntegration
 *
 * @brief Plugin class for the ScholarOne Integration plugin.
 */

namespace APP\plugins\generic\scholarOneIntegration;

use PKP\plugins\GenericPlugin;
use APP\core\Application;

class ScholarOneIntegrationPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return true;
        }

        // if ($success && $this->getEnabled($mainContextId)) {
        //     // Add hooks
        // }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.scholarOneIntegration.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.scholarOneIntegration.description');
    }
}
