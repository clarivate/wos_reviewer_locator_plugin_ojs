{**
 * plugins/generic/wosReviewerLocator/templates/settings.tpl
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Web of Science - Reviewer Locator - settings template
 *}

<script>
    $(function() {ldelim}
        $('#wosRLForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div>
    <form class="pkp_form" id="wosRLForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="connect" save=true}">
        {csrf}
        <p>{translate key="plugins.generic.wosrl.settings.info"}</p>
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="wosRLFormNotification"}
        {fbvFormArea id="wosRLFormArea"}
            <table width="100%" class="data">
                <tr valign="top">
                    <td class="label">{fieldLabel name="api_key" key="plugins.generic.wosrl.settings.api_key"}</td>
                    <td class="value">
                        {fbvElement type="text" id="api_key" name="api_key" value="$api_key" label="plugins.generic.wosrl.settings.api_key_description"}
                    </td>
                </tr>
                <tr valign="top">
                    <td class="label">{fieldLabel name="nor" key="plugins.generic.wosrl.settings.nor"}</td>
                    <td class="value">
                        {fbvElement type="select" id="nor" name="nor" from=$recommendations translate=false selected=$nor label="plugins.generic.wosrl.settings.nor_description"}
                    </td>
                </tr>
            </table>
        {/fbvFormArea}
        {fbvFormButtons}
    </form>
</div>
<p>{translate key="plugins.generic.wosrl.settings.ps"}</p>
