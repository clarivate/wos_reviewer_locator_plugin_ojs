<?php

/**
 * @file wosReviewerLocatorPlugin.php
 *
 * @class wosReviewerLocatorPlugin
 */

namespace APP\plugins\generic\wosReviewerLocator;

use APP\core\Application;
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

class wosReviewerLocatorPlugin extends GenericPlugin {

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);
            Hook::add('TemplateManager::fetch', [$this, 'handleTemplateFetch']);
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
        if($params[0] == 'wosrl' && $this->getEnabled()) {
            if ($params[1] == 'getReviewerList') {
                define('HANDLER_CLASS', wosRLHandler::class);
                wosRLHandler::setPlugin($this);
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
        if($this->getEnabled()) {
            $templateManager = $args[0];
            // Assign our private stylesheet, for front and back ends.
            $templateManager->addStyleSheet(
                'wosReviewerLocator',
                $request->getBaseUrl() . '/' . $this->getStyleSheet(),
                ['contexts' => ['frontend', 'backend']]
            );
            $templateManager->addJavaScript(
                'wosReviewerLocatorPagination',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/jquery.paging.min.js',
                ['contexts' => ['frontend', 'backend']]
            );
            $templateManager->addJavaScript(
                'wosReviewerLocator',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/wosrl.js',
                ['contexts' => ['frontend', 'backend']]
            );
        }
        return false;
    }

    /**
     * Hook callback: register output filter to display results.
     * @see TemplateManager::fetch()
     */
    function handleTemplateFetch($hookName, $args): bool
    {
        if ($this->getEnabled() && $args[1] == 'controllers/grid/gridBodyPart.tpl') {
            $args[0]->registerFilter('output', [$this, 'reviewPageFilter']);
        }
        return false;
    }

    function reviewPageFilter($output, $templateManager)
    {
        $request = Application::get()->getRequest();
        $plugin = PluginRegistry::getPlugin('generic', $this->getName());
        $journalId = $request->getContext()->getId();
        $api_key = $plugin->getSetting($journalId, 'api_key');
        $exp = explode('/', $request->getRequestPath());
        preg_match_all('/class="pkp_controllers_linkAction pkp_linkaction_addReviewer pkp_linkaction_icon_add_user"/s', $output, $matches);
        if($api_key && in_array('reviewer-grid', $exp) && $matches && $matches[0]) {
            $args = $request->getQueryArray();
            $page_url = $request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'wosrl', 'getReviewerList', null, ['submissionId' => $args['submissionId']]);
            $templateManager->assign('page_url', $page_url);
            $wosRLDao = new wosRLDAO();
            $token = $wosRLDao->getToken($args['submissionId']);
            if($token && \Carbon\Carbon::now()->diff($token->created_at)->days >= 60) {
                $wosRLDao->deleteObject($token->submission_id);
                $token = null;
            }
            $templateManager->assign('wosrl_token', $token);
            $output .= $templateManager->fetch($plugin->getTemplateResource('grid.tpl'));
        }
        $templateManager->unregisterFilter('output', 'reviewPageFilter');
        return $output;
    }

}

if ( ! PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\wosReviewerLocator\wosReviewerLocatorPlugin', '\wosReviewerLocatorPlugin');
}

