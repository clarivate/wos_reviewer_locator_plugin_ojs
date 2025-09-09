{**
 * plugins/generic/wosReviewerLocator/templates/grid.tpl
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Web of Science - Reviewer Locator plugin - grid template
 *}

<div class="pkp_controllers_grid" data-page-url="{$page_url}" data-has-token="{if $wosrl_token}true{else}false{/if}">
    <div id="wosRLHeader" class="header">
        <h4>{translate key="plugins.generic.wosrl.grid.title"}</h4>
        <ul class="actions">
            <li>
                <a id="wosRLSearch" href="#" onclick="return wosRLList('{$page_url}');" class="pkp_controllers_linkAction pkp_linkaction_search{($wosrl_token) ? ' wosrl-hidden' : ''}">
                    {translate key="plugins.generic.wosrl.grid.search"}
                </a>
            </li>
            <li><a href="#" class="wosrl-toggle"></a></li>
        </ul>
    </div>
    <div id="wosRLGrid">
        <div class="wosrl-placeholder">
            {translate key="plugins.generic.wosrl.list.placeholder"}
        </div>
    </div>
</div>
