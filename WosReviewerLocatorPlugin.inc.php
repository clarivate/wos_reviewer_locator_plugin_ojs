<?php

/**
 * @file WosReviewerLocatorPlugin.php
 *
 * @class WosReviewerLocatorPlugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class WosReviewerLocatorPlugin extends GenericPlugin {

    /**
     * @copydoc Plugin::register()
     */
    function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            $this->import('classes.WosRLDAO');
            DAORegistry::registerDAO('WosRLDAO', new WosRLDao());
            HookRegistry::register('TemplateManager::display', array(&$this, 'handleTemplateDisplay'));
            HookRegistry::register('TemplateManager::fetch', array(&$this, 'handleTemplateFetch'));
            HookRegistry::register('LoadHandler', array(&$this, 'loadHandler'));
        }
        return $success;
    }

    /**
     * Get the symbolic name of this plugin
     * @return string
     */
    function getName() {
        return 'WosReviewerLocatorPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    function getDisplayName()
    {
        return __('plugins.generic.wosrl.display_name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    function getDescription()
    {
        return __('plugins.generic.wosrl.description');
    }

    /**
     * @see Plugin::getInstallSchemaFile()
     * @return string
     */
    function getInstallSchemaFile() {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml';
    }

    /**
     * Get the stylesheet for this plugin
     *
     * @return string
     */
    function getStyleSheet()
    {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'wosrl.css';
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
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
                $this->import('classes.WosRLForm');
                $form = new WosRLForm($this, $context->getId());
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

    function loadHandler($hookName, $params)
    {
        if($params[0] == 'wosrl' && $this->getEnabled()) {
            if ($params[1] == 'getReviewerList') {
                define('HANDLER_CLASS', 'WosRLHandler');
                $this->import('WosRLHandler');
                WosRLHandler::setPlugin($this);
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
    function handleTemplateDisplay($hookName, $args)
    {
        $request = PKPApplication::getRequest();
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
    function handleTemplateFetch($hookName, $args)
    {
        if ($this->getEnabled() && $args[1] == 'controllers/grid/grid.tpl') {
            $args[0]->registerFilter('output', [$this, 'reviewPageFilter']);
        }
        return false;
    }

    function reviewPageFilter($output, $templateManager)
    {
        $request = PKPApplication::getRequest();
        $plugin = PluginRegistry::getPlugin('generic', $this->getName());
        $journalId = $request->getContext()->getId();
        $api_key = $plugin->getSetting($journalId, 'api_key');
        $exp = explode('/', $request->getRequestPath());
        preg_match('/pkp_linkaction_addReviewer/s', $output, $matches);
        if($api_key && in_array('reviewer-grid', $exp) && $matches && $matches[0]) {
            $args = $request->getQueryArray();
            $page_url = $request->getDispatcher()->url($request, ROUTE_PAGE, null, 'wosrl', 'getReviewerList', null, [
                'submissionId' => $args['submissionId'],
                'stageId' => $args['stageId']
            ]);
            $templateManager->assign('page_url', $page_url);
            $wosRLDao =& DAORegistry::getDAO('WosRLDAO');
            $token = $wosRLDao->getToken($args['submissionId']);
            if($token && \Carbon\Carbon::now()->diffInDays($token['created_at']) >= 60) {
                $wosRLDao->deleteObject($token['submission_id']);
                $token = null;
            }
            $templateManager->assign('wosrl_token', $token);
            $output .= $templateManager->fetch($plugin->getTemplateResource('grid.tpl'));
        }
        $templateManager->unregisterFilter('output', 'reviewPageFilter');
        return $output;
    }

}

