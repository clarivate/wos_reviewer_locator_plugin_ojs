<?php

/**
 * @file plugins/generic/wosReviewerLocator/classes/WosRLForm.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WosRLForm
 *
 * @brief Plugin settings: connect to a Web of Science - Reviewer Locator service
 */

import('lib.pkp.classes.form.Form');

class WosRLForm extends Form {

    /** @var $_plugin object */
    var $_plugin;

    /** @var $_journalId int */
    var $_journalId;

    /**
     * Constructor
     *
     * @param $plugin WosReviewerLocatorPlugin
     * @param $journalId int
     * @see Form::Form()
     */
    function __construct($plugin, $journalId) {
        $this->_plugin = $plugin;
        $this->_journalId = $journalId;
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->addCheck(new FormValidator($this, 'api_key', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrl.settings.api_key_required'));
        $this->addCheck(new FormValidator($this, 'nor', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrl.settings.nor_required'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @see Form::initData()
     */
    function initData() {
        $this->setData('api_key', $this->_plugin->getSetting($this->_journalId, 'api_key'));
        $this->setData('nor', $this->_plugin->getSetting($this->_journalId, 'nor') ?? 30);
    }

    /**
     * @see Form::readInputData()
     */
    function readInputData() {
        $this->readUserVars(['api_key']);
        $this->readUserVars(['nor']);
    }

    /**
     * Fetch the form
     *
     * @copydoc Form::fetch()
     * @throws Exception
     */
    function fetch($request, $template = null, $display = false) {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign('pluginName', $this->_plugin->getName());
        $range = range(15, 100);
        $templateManager->assign('recommendations', array_combine($range, $range));
        return parent::fetch($request, $template, $display);
    }

    /**
     * @see Form::execute()
     */
    function execute(...$functionArgs) {
        $this->_plugin->updateSetting($this->_journalId, 'api_key', $this->getData('api_key'), 'string');
        $this->_plugin->updateSetting($this->_journalId, 'nor', $this->getData('nor'), 'int');
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            PKPApplication::getRequest()->getUser()->getId(),
            NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.wosrl.notifications.settings_updated')]
        );
        parent::execute(...$functionArgs);
    }

}
