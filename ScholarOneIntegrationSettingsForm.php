<?php

namespace APP\plugins\generic\scholarOneIntegration;

use PKP\form\Form;
use PKP\plugins\Plugin;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\form\validation\FormValidator;

class ScholarOneIntegrationSettingsForm extends Form
{
    private $plugin;
    private $contextId;
    private const CONFIG_VARS = [
        'journalShortName' => 'string',
        'clientKey' => 'string',
        'accessKey' => 'string',
        'privateKey' => 'string'
    ];

    public function __construct(Plugin $plugin, int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->plugin = $plugin;
        $this->contextId = $contextId;
    }

    public function initData()
    {
        $this->_data = [];
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $this->_data[$configVar] = $this->plugin->getSetting($this->contextId, $configVar);
        }
    }

    public function readInputData()
    {
        $this->readUserVars(array_keys(self::CONFIG_VARS));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $this->plugin->updateSetting($this->contextId, $configVar, $this->getData($configVar), $type);
        }
        parent::execute(...$functionArgs);
    }
}
