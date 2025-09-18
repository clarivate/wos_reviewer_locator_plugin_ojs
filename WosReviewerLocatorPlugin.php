<?php

/**
 * @file WosReviewerLocatorPlugin.php
 *
 * @class WosReviewerLocatorPlugin
 */

namespace APP\plugins\generic\wosReviewerLocator;

use APP\core\Application;
use APP\facades\Repo;
use PKP\core\JSONMessage;
use APP\template\TemplateManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\db\DAO;

use APP\plugins\generic\wosReviewerLocator\classes\wosRLForm;
use APP\plugins\generic\wosReviewerLocator\classes\wosRLMigration;
use APP\plugins\generic\wosReviewerLocator\classes\wosRLDAO;

class WosReviewerLocatorPlugin extends GenericPlugin {

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);
            Hook::add('LoadHandler', [$this, 'loadHandler']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.wosrl.display_name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.wosrl.description');
    }

    /**
     * @copydoc Plugin::getInstallMigration()
     */
    public function getInstallMigration(): wosRLMigration
    {
        return new wosRLMigration();
    }

    /**
     * Get the stylesheet for this plugin
     *
     * @return string
     */
    function getStyleSheet(): string
    {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'wosrl.css';
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'connect',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, [
                            'verb' => 'connect',
                            'plugin' => $this->getName(),
                            'category' => 'generic'
                        ]),
                        $this->getDisplayName()
                    ),
                    __('plugins.generic.wosrl.settings.connection'),
                    null
                ),
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    /**
     * @see GenericPlugin::manage()
     */
    function manage($args, $request) {
        switch ($request->getUserVar('verb')) {
            case 'connect':
                $context = $request->getContext();
                $form = new wosRLForm($this, $context->getId());
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    function loadHandler($hookName, $params): bool
    {
        $page = &$params[0];
        if ($page == 'wosrl' && $this->getEnabled()) {
            $op = &$params[1];
            $handler = &$params[3];
            if ($op == 'getReviewerList' || $op == 'getTemplate') {
                $wosHandler = new wosRLHandler();
                $wosHandler->setPlugin($this);
                $handler = $wosHandler;
                return true;
            }
        }
        return false;
    }

    /**
     * Hook callback: register output filter to add data citation to submission
     * summaries; add data citation to reading tools' suppfiles and metadata views.
     * @see TemplateManager::display()
     */
    function handleTemplateDisplay($hookName, $args): bool
    {
        $request = Application::get()->getRequest();
        $journalId = $request->getContext()->getId();
        $api_key = $this->getSetting($journalId, 'api_key');
        $templateManager = $args[0];
        $template = $args[1];
        $params = $request->getQueryArray();
        $submissionId = isset($params['workflowSubmissionId']) ? $params['workflowSubmissionId'] : null;
        if($this->getEnabled() && $template === 'dashboard/editors.tpl' && $api_key && $submissionId) {
            // Assign our private stylesheet, for front and back ends.
            $templateManager->addStyleSheet(
                'wosReviewerLocator',
                $request->getBaseUrl() . '/' . $this->getStyleSheet(),
                ['contexts' => ['frontend', 'backend']]
            );
            $templateManager->addJavaScript(
                'wosReviewerLocatorPagination',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/jquery.paging.min.js',
                ['contexts' => ['frontend', 'backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_LAST]
            );
            $templateManager->addJavaScript(
                'wosReviewerLocator',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/wosrl.js',
                ['contexts' => ['backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_LAST]
            );
            // Get the submission to retrieve its current stage ID
            $submission = Repo::submission()->get((int)$submissionId);
            $stageId = $submission ? $submission->getData('stageId') : null;
            // Pass simple configuration to JavaScript
            $config = '
                window.wosReviewerLocatorConfig = {
                    apiKey: ' . json_encode($api_key) . ',
                    templateUrl: ' . json_encode($request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'wosrl', 'getTemplate', null, [
                    'submissionId' => $submissionId,
                    'stageId' => $stageId
                ])) . ',
                    ready: true
                };
            ';
            $templateManager->addJavaScript('wosReviewerLocatorConfig', $config, ['inline' => true, 'contexts' => ['backend']]);
        }
        return false;
    }

}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\wosReviewerLocator\WosReviewerLocatorPlugin', '\WosReviewerLocatorPlugin');
}

