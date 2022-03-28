//{namespace name="backend/mollie/support/view/detail/collected_data"}
Ext.define('Shopware.apps.MollieSupport.view.detail.CollectedData', {
    extend: 'Ext.Component',
    ui: 'shopware-ui',
    id: 'mollieSupportCollectedData',
    alias: 'widget.mollieSupportCollectedData',

    html: [
        '<b>Shopware:</b><br />...<br /><br />',
        '<b>Mollie Shopware:</b><br />...<br /><br />',
        '<b>{s name=labelAttachments}Attachments{/s}:</b><br />{s name=labelPluginLogs}Mollie Shopware log files{/s}<br /><br />',
    ]
});
