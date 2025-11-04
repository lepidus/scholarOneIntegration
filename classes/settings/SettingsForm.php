<?php

namespace APP\plugins\generic\scholarOneIntegration\classes\settings;

use PKP\form\Form;
use PKP\plugins\Plugin;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\form\validation\FormValidator;
use APP\plugins\generic\scholarOneIntegration\classes\APIKeyEncryption;

class SettingsForm extends Form
{
    private $plugin;
    private $contextId;
    private $encrypter;
    private const CONFIG_VARS = [
        'journalShortName' => 'string',
        'clientKey' => 'string',
        'accessKey' => 'string',
        'secretKey' => 'string'
    ];
    private const ENCRYPTED_VARS = [
        'clientKey',
        'accessKey',
        'secretKey'
    ];

    public function __construct(Plugin $plugin, int $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->encrypter = new APIKeyEncryption();

        $template = $this->encrypter->secretConfigExists()
            ? 'settingsForm.tpl'
            : 'settingsFormEmptySecret.tpl';

        parent::__construct($plugin->getTemplateResource($template));
    }

    public function initData()
    {
        $this->_data = [];
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $settingValue = $this->plugin->getSetting($this->contextId, $configVar);

            if (
                in_array($configVar, self::ENCRYPTED_VARS)
                && !empty($settingValue)
                && $this->encrypter->textIsEncrypted($settingValue)
            ) {
                $settingValue = $this->encrypter->decryptString($settingValue);
            }

            $this->_data[$configVar] = $settingValue;
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
            $settingValue = $this->getData($configVar);

            if (in_array($configVar, self::ENCRYPTED_VARS)) {
                $settingValue = $this->encrypter->encryptString($settingValue);
            }

            $this->plugin->updateSetting($this->contextId, $configVar, $settingValue, $type);
        }
        parent::execute(...$functionArgs);
    }
}
