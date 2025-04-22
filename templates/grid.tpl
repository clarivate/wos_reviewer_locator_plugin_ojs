{**
 * plugins/generic/wosReviewerLocator/templates/grid.tpl
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Web of Science - Reviewer Locator plugin - grid template
 *}

<div class="pkp_controllers_grid">
    <div id="wosRLHeader" class="header">
        <h4>{translate key="plugins.generic.wosrl.grid.title"}</h4>
        <ul class="actions{($wosrl_token) ? ' pkp_helpers_display_none' : ''}">
            <li>
                <a href="#" onclick="return wosRLList('{$page_url}');" class="pkp_controllers_linkAction pkp_linkaction_search">
                    {translate key="plugins.generic.wosrl.grid.search"}
                </a>
                <script type="text/javascript">
                    $(function() {
                        {if $wosrl_token}wosRLList('{$page_url}');{/if}
                    });
                </script>
            </li>
        </ul>
    </div>
    <div id="wosRLGrid">
        <div class="wosrl-placeholder">
            {translate key="plugins.generic.wosrl.list.placeholder"}
        </div>
    </div>
</div>
