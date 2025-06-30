<?php

/**
 * @file plugins/generic/wosReviewerLocator/WosRLHandler.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WosRLHandler
 *
 * @brief Handle Web of Science - Reviewer Locator requests
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class WosRLHandler extends Handler
{

    /** @var WosReviewerLocatorPlugin The Web of Science - Reviewer Locator plugin */
    static $plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN], 'getReviewerList');
    }

    /**
     * @copydoc PKPHandler::authorize()
     * @return bool
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Set plugin
     *
     * @param $plugin
     * @return void
     */
    static function setPlugin($plugin)
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
        // Get submission & publication
        $submissionDAO = DAORegistry::getDAO('ReviewerSubmissionDAO');
        $submission_id = $args['submissionId'];
        $submission = $submissionDAO->getReviewerSubmission($submission_id);
        // Fetch token and check expiration
        $wosRLDao = DAORegistry::getDAO('WosRLDAO');
        $token = $wosRLDao->getToken($submission_id);
        // Fetch records
        $url = 'https://api.clarivate.com/api/wosrl';
        $headers = [
            'X-ApiKey: ' . $plugin->getSetting($context->getId(), 'api_key'),
            'Content-Type: application/json'
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if( ! $token) {
            // Set post data
            $data = [
                'requestId' => $wosRLDao->getNextId(),
                'searchArticle' => [
                    'title' => $submission->getLocalizedData('title'),
                    'abstract' => strip_tags($submission->getLocalizedData('abstract')),
                    'journal' => [
                        'name' => $context->getLocalizedName()
                    ],
                    'authors' => []
                ],
                'excludedReviewers' => [],
                'searchYears' => 5,
                'numRecommendations' => $plugin->getSetting($context->getId(), 'nor')
            ];
            $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
            $userGroups = $userGroupDao->getByContextId($context->getId())->toArray();
            $userGroupsAuthorIds = array_map(function ($userGroup) {
                return $userGroup->getId();
            }, array_filter($userGroups, function ($userGroup) {
                return in_array($userGroup->getRoleId(), [ROLE_ID_AUTHOR]);
            }));
            $userGroupsEditorIds = array_map(function ($userGroup) {
                return $userGroup->getId();
            }, array_filter($userGroups, function ($userGroup) {
                return in_array($userGroup->getRoleId(), [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR]);
            }));
            $userDao = DAORegistry::getDAO('UserDAO');
            $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
            $stageAssignmentFactory = $stageAssignmentDao->getBySubmissionAndStageId($submission_id, $args['stageId']);
            while ($stageAssignment = $stageAssignmentFactory->next()) {
                $userId = $stageAssignment->getUserId();
                $user = $userDao->getById($userId);
                if (in_array($stageAssignment->getUserGroupId(), $userGroupsAuthorIds)) {
                    $data_author = [
                        'firstName' => $user->getLocalizedGivenName(),
                        'lastName' => $user->getLocalizedFamilyName(),
                        'email' => $user->getEmail(),
                        'organizations' => []
                    ];
                    if($affiliation = $user->getLocalizedAffiliation()) {
                        $data_author['organizations'][] = [
                            'name' => $affiliation
                        ];
                    };
                    $data['searchArticle']['authors'][] = $data_author;
                }
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
                $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
                $json_data = str_replace("\\\\", '\\', $json_data);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
                $response = curl_exec($curl);
                if(curl_error($curl)) {
                    return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
                }
                $body = json_decode($response);
                $wosRLDao->insertObject($submission_id, [
                    'token' => $body->searchToken
                ]);
                $reviewers = $body->recommendedReviewers;
                $templateManager->assign('token', $body->searchToken);
                $templateManager->assign('status', curl_getinfo($curl, CURLINFO_HTTP_CODE));
                curl_close($curl);
            } catch (\Exception $e) {
                return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
            }
        } else {
            try {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $url . '/' . $token['token'] . '/');
                $response = curl_exec($curl);
                if(curl_error($curl)) {
                    return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
                }
                $body = json_decode($response);
                $reviewers = $body->recommendedReviewers;
                $templateManager->assign('token', $token['token']);
                $templateManager->assign('status', curl_getinfo($curl, CURLINFO_HTTP_CODE));
                curl_close($curl);
            } catch (\Exception $e) {
                $wosRLDao->deleteObject($token['submission_id']);
                return new JSONMessage(false, Locale::get('plugins.generic.wosrl.error.general'));
            }
        }
        // Return formatted list, or empty template
        $templateManager->assign('reviewers', $reviewers);
        return $templateManager->fetchJson($plugin->getTemplateResource($reviewers ? 'list.tpl' : 'empty.tpl'));
    }

}
