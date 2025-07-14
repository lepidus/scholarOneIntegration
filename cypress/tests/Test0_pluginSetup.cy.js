describe('ScholarOne Integration - Plugin setup', function () {
	const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-scholaroneintegrationplugin';
	
	it('Enables and configures ScholarOne Integration plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-scholaroneintegrationplugin]').check();
		cy.get('input[id^=select-cell-scholaroneintegrationplugin]').should('be.checked');

        cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();

        cy.contains('Journal Short Name');
        cy.get('input[name=journalShortName]').focus().clear();
        cy.contains('Client Key');
		cy.get('input[name=clientKey]').focus().clear();
        cy.contains('Access Key');
		cy.get('input[name=accessKey]').focus().clear();
        cy.contains('Secret Key');
        cy.get('input[name=secretKey]').focus().clear();

		cy.get('#scholarOneIntegrationSettingsForm button:contains("OK")').click();
		cy.get('label[for^=journalShortName].error').should('contain', 'This field is required.');
		cy.get('label[for^=clientKey].error').should('contain', 'This field is required.');
		cy.get('label[for^=accessKey].error').should('contain', 'This field is required.');
		cy.get('label[for^=secretKey].error').should('contain', 'This field is required.');

        cy.get('input[name=journalShortName]').focus().type('testjournalname');
		cy.get('input[name=clientKey]').focus().type('testclientkey');
		cy.get('input[name=accessKey]').focus().type('testaccesskey');
        cy.get('input[name=secretKey]').focus().type('testsecretkey');

        cy.get('#scholarOneIntegrationSettingsForm button:contains("OK")').click();
		cy.get('div:contains("Your changes have been saved.")');
	});
});