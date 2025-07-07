<script>
    $(function() {ldelim}
        $('#scholarOneIntegrationSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<form class="pkp_form" id="scholarOneIntegrationSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    {csrf}
    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="scholarOneIntegrationSettingsFormNotification"}
    {fbvFormArea id="scholarOneIntegrationSettings"}
        {fbvFormSection label="plugins.generic.scholarOneIntegration.settings.journalShortName" required=true}
            {fbvElement
                type="text"
                id="journalShortName"
                label="plugins.generic.scholarOneIntegration.settings.journalShortName.description"
                value=$journalShortName|escape
                size=$fbvStyles.size.MEDIUM
            }
        {/fbvFormSection}
        {fbvFormSection label="plugins.generic.scholarOneIntegration.settings.clientKey" required=true}
            {fbvElement
                type="text"
                password="true"
                id="clientKey"
                label="plugins.generic.scholarOneIntegration.settings.clientKey.description"
                value=$clientKey|escape
                size=$fbvStyles.size.MEDIUM
            }
        {/fbvFormSection}
        {fbvFormSection label="plugins.generic.scholarOneIntegration.settings.accessKey" required=true}
            {fbvElement
                type="text"
                password="true"
                id="accessKey"
                label="plugins.generic.scholarOneIntegration.settings.accessKey.description"
                value=$accessKey|escape
                size=$fbvStyles.size.MEDIUM
            }
        {/fbvFormSection}
        {fbvFormSection label="plugins.generic.scholarOneIntegration.settings.privateKey" required=true}
            {fbvElement
                type="text"
                password="true"
                id="privateKey"
                label="plugins.generic.scholarOneIntegration.settings.privateKey.description"
                value=$privateKey|escape
                size=$fbvStyles.size.MEDIUM
            }
        {/fbvFormSection}
    {/fbvFormArea}
    {fbvFormButtons}
</form>
