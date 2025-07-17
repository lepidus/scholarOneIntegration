<?php

namespace APP\plugins\generic\scholarOneIntegration\classes\settings;

use PKP\core\JSONMessage;
use APP\notification\NotificationManager;

class Manage
{
    private $plugin;

    public function __construct(&$plugin)
    {
        $this->plugin = &$plugin;
    }

    public function execute($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $contextId = ($context == null) ? 0 : $context->getId();

                $form = new SettingsForm($this->plugin, $contextId);
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        $notificationManager = new NotificationManager();
                        $notificationManager->createTrivialNotification($request->getUser()->getId());
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }

                return new JSONMessage(true, $form->fetch($request));
        }

        return new JSONMessage(false);
    }
}
