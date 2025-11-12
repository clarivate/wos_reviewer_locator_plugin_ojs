<?php

/**
 * @file plugins/generic/wosReviewerLocator/classes/wosRLForm.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class wosRLForm
 *
 * @brief Plugin settings: connect to a Web of Science - Reviewer Locator service
 */

namespace APP\plugins\generic\wosReviewerLocator\classes;

use APP\core\Application;
use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use Exception;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\notification\Notification;
use APP\plugins\generic\wosReviewerLocator\WosReviewerLocatorPlugin;

class wosRLForm extends Form {

    /** @var $_plugin object */
    var object $_plugin;

    /** @var $_journalId int */
    var int $_journalId;

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
        $this->addCheck(new FormValidator($this, 'api_key', FormValidator::FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrl.settings.api_key_required'));
        $this->addCheck(new FormValidator($this, 'nor', FormValidator::FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrl.settings.nor_required'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @see Form::initData()
     */
    function initData(): void {
        $this->setData('api_key', $this->_plugin->getSetting($this->_journalId, 'api_key'));
        $this->setData('nor', $this->_plugin->getSetting($this->_journalId, 'nor') ?? 30);
    }

    /**
     * @see Form::readInputData()
     */
    function readInputData(): void {
        $this->readUserVars(['api_key']);
        $this->readUserVars(['nor']);
    }

    /**
     * @see Form::validate()
     */
    function validate($callHooks = true): bool {
        // Run parent validation (required fields, CSRF, etc.)
        if (!parent::validate($callHooks)) {
            return false;
        }

        // Validate API key by making a test call to the API
        $apiKey = $this->getData('api_key');
        if (!empty($apiKey)) {
            $httpClient = Application::get()->getHttpClient();
            $testUrl = 'https://api.clarivate.com/api/wosrl/1/';
            $headers = [
                'X-ApiKey' => $apiKey,
                'Content-Type' => 'application/json'
            ];

            try {
                // Make test request with http_errors = false to handle status codes manually
                // 404: Valid API key (token 1 does not exist)
                // 401: Invalid API key
                $response = $httpClient->request('GET', $testUrl, [
                    'headers' => $headers,
                    'http_errors' => false
                ]);

                $statusCode = $response->getStatusCode();

                // 404 means the API key is valid (the test token does not exist, which is expected)
                if ($statusCode === 404) {
                    return true;
                }

                // 401 means invalid API key or authentication error
                if ($statusCode === 401) {
                    $this->addError('api_key', __('plugins.generic.wosrl.settings.api_key_required'));
                    $this->addErrorField('api_key');
                    return false;
                }

                // Any other status code is unexpected
                $this->addError('api_key', __('plugins.generic.wosrl.settings.api_key_validation_failed'));
                $this->addErrorField('api_key');
                return false;

            } catch (Exception $e) {
                // Network error or other exception
                $this->addError('api_key', __('plugins.generic.wosrl.settings.api_key_validation_error', ['error' => $e->getMessage()]));
                $this->addErrorField('api_key');
                return false;
            }
        }
        return true;
    }

    /**
     * Fetch the form
     *
     * @copydoc Form::fetch()
     * @throws Exception
     */
    function fetch($request, $template = null, $display = false): ?string {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign('pluginName', $this->_plugin->getName());
        $range = range(30, 100, 10);
        $templateManager->assign('recommendations', array_combine($range, $range));
        return parent::fetch($request, $template, $display);
    }

    /**
     * @see Form::execute()
     */
    function execute(...$functionArgs): void {
        $this->_plugin->updateSetting($this->_journalId, 'api_key', $this->getData('api_key'), 'string');
        $this->_plugin->updateSetting($this->_journalId, 'nor', $this->getData('nor'), 'int');
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            Notification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.wosrl.notifications.settings_updated')]
        );
        parent::execute(...$functionArgs);
    }

}
