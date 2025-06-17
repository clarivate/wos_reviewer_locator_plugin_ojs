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
        <ul class="actions">
            <li>
                <a id="wosRLSearch" href="#" onclick="return wosRLList('{$page_url}');" class="pkp_controllers_linkAction pkp_linkaction_search{($wosrl_token) ? ' wosrl-hidden' : ''}">
                    {translate key="plugins.generic.wosrl.grid.search"}
                </a>
            </li>
            <li><a href="#" class="wosrl-toggle"></a></li>
        </ul>
        <script type="text/javascript">
            $(function() {
                const wrapper = $('#wosRLHeader');
                $('a.wosrl-toggle', wrapper).on('click', function() {
                    $(this).toggleClass('closed');
                    $('a#wosRLSearch', wrapper).toggleClass('pkp_helpers_display_none');
                    $('#wosRLGrid').toggleClass('pkp_helpers_display_none');
                    return false;
                });
                {if $wosrl_token}wosRLList('{$page_url}');{/if}
            });
        </script>
    </div>
    <div id="wosRLGrid">
        <div class="wosrl-placeholder">
            {translate key="plugins.generic.wosrl.list.placeholder"}
        </div>
    </div>
</div>
