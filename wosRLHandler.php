<?php

/**
 * @file plugins/generic/wosReviewerLocator/wosRLHandler.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class wosRLHandler
 *
 * @brief Handle Web of Science - Reviewer Locator requests
 */

namespace APP\plugins\generic\wosReviewerLocator;

use APP\core\Application;
use APP\handler\Handler;
use APP\core\Request;
use APP\facades\Repo;
use APP\template\TemplateManager;

use PKP\db\DAORegistry;
use PKP\core\JSONMessage;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\security\authorization\ContextAccessPolicy;

use APP\plugins\generic\wosReviewerLocator\classes\wosRLDAO;

class wosRLHandler extends Handler
{

    /** @var WosReviewerLocatorPlugin The Web of Science - Reviewer Locator plugin */
    static WosReviewerLocatorPlugin $plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN], 'getReviewerList');
    }

    /**
     * @copydoc PKPHandler::authorize()
     * @return bool
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Set plugin
     *
     * @param $plugin
     * @return void
     */
    static function setPlugin($plugin): void
    {
        self::$plugin = $plugin;
    }

    /**
     * Get reviewer list from the API
     *
     * @param array $args
     * @param Request $request
     * @return \PKP\core\JSONMessage|void
     * @throws \Exception
     */
    function getReviewerList(array $args, $request)
    {
        set_time_limit(0);
        $args = $request->getQueryArray();
        $plugin = self::$plugin;
        $context = $request->getContext();
        $templateManager = TemplateManager::getManager();
        $httpClient = Application::get()->getHttpClient();
        // Get submission & publication
        $submission_id = $args['submissionId'];
        $submission = Repo::submission()->get($submission_id);
        $publication = $submission->getCurrentPublication();
        // Fetch token and check expiration
        $wosRLDao = new wosRLDAO();
        $token = $wosRLDao->getToken($submission_id);
        // Fetch records
        $url = 'https://api.clarivate.com/api/wosrl';
        $headers = [
            'X-ApiKey' => $plugin->getSetting($context->getId(), 'api_key'),
            'Content-Type' => 'application/json'
        ];
        if( ! $token) {
            // Set post data
            $data = [
                'requestId' => $wosRLDao->getNextId(),
                'searchArticle' => [
                    'title' => $publication->getLocalizedData('title'),
                    'abstract' => strip_tags($publication->getLocalizedData('abstract')),
                    'journal' => [
                        'name' => $context->getLocalizedName()
                    ],
                    'authors' => []
                ],
                'excludedReviewers' => [],
                'searchYears' => 5,
                'numRecommendations' => $plugin->getSetting($context->getId(), 'nor')
            ];
            // Authors
            foreach ($publication->getData('authors') as $author) {
                $data_author = [
                    'firstName' => $author->getLocalizedGivenName(),
                    'lastName' => $author->getLocalizedFamilyName(),
                    'email' => $author->getEmail(),
                    'organizations' => []
                ];
                if($affiliation = $author->getLocalizedAffiliation()) {
                    $data_author['organizations'][] = [
                        'name' => $affiliation
                    ];
                };
                $data['searchArticle']['authors'][] = $data_author;
            }
            // Editors
            $userGroups = Repo::userGroup()->getCollector()->filterByContextIds([$context->getId()])->getMany()->toArray();
            $userGroupsEditorIds = array_map(function ($userGroup) {
                return $userGroup->getId();
            }, array_filter($userGroups, function ($userGroup) {
                return in_array($userGroup->getRoleId(), [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR]);
            }));
            $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
            $stageAssignmentFactory = $stageAssignmentDao->getBySubmissionAndStageId($submission_id, $args['stageId']);
            while ($stageAssignment = $stageAssignmentFactory->next()) {
                $userId = $stageAssignment->getUserId();
                $user = Repo::user()->get($userId);
                if (in_array($stageAssignment->getUserGroupId(), $userGroupsEditorIds)) {
                    $data_editor = [
                        'firstName' => $user->getLocalizedGivenName(),
                        'lastName' => $user->getLocalizedFamilyName(),
                        'email' => $user->getEmail()
                    ];
                    $data['excludedReviewers'][] = $data_editor;
                }
            }
            try {
                $response = $httpClient->request('POST', $url, [
                    'headers' => $headers,
                    'body' => json_encode($data, JSON_UNESCAPED_UNICODE)
                ]);
                $body = json_decode($response->getBody());
                $wosRLDao->insertObject($submission_id, [
                    'token' => $body->searchToken
                ]);
                $reviewers = $body->recommendedReviewers;
                $templateManager->assign('token', $body->searchToken);
                $templateManager->assign('status', $response->getStatusCode());
            } catch (\Exception $e) {
                return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
            }
        } else {
            try {
                $response = $httpClient->request('GET', $url . '/' . $token->token . '/', [
                    'headers' => $headers
                ]);
                $body = json_decode($response->getBody());
                $reviewers = $body->recommendedReviewers;
                $templateManager->assign('token', $token->token);
                $templateManager->assign('status', $response->getStatusCode());
            } catch (\Exception $e) {
                // Temporary delete token...
                $wosRLDao->deleteObject($token->submission_id);
                return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
            }
        }
        // Return formatted list, or empty template
        $templateManager->assign('reviewers', $reviewers);
        return $templateManager->fetchJson($plugin->getTemplateResource($reviewers ? 'list.tpl' : 'empty.tpl'));
    }

}
