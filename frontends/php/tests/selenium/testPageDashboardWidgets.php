<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require_once dirname(__FILE__) . '/../include/CWebTest.php';

/**
 * @backup dashboard
 * @backup profiles
 */
class testPageDashboardWidgets extends CWebTest {

	/**
	 * Default selected widget type.
	 * The widget type should not be changed in frontend and in DB.
	 */
	public function testPageDashboardWidgets_checkUnchangedWidgetType() {
		// Opening widget configuration form for new widget first time.
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=1');
		$dashboard = CDashboardElement::find()->one()->edit();
		// Check that widget type isn't changed in frontend and in DB.
		$this->checkLastSelectedWidgetType();

		// Opening edit widget form.
		$form = $dashboard->getWidget('System information')->edit();
		$this->assertEquals('System information', $form->getField('Type')->getValue());
		$form->submit();
		// Check that widget type isn't changed in frontend and in DB.
		$this->checkLastSelectedWidgetType();

		// Making changes in widget form that are not "Widget type".
		$form = $dashboard->getWidget('Problems')->edit();
		$this->assertEquals('Problems', $form->getField('Type')->getValue());
		$data =[
			'Name' => 'check widget type',
			'Refresh interval' => 'No refresh',
			'Show' => 'Recent problems',
			'Show tags' => 'None'
		];
		$form->fill($data);
		$form->submit();
		$this->checkLastSelectedWidgetType();

		// Add widget with current default type "Action log".
		$form = $dashboard->addWidget()->asForm();
		$form->submit();
		// Check if widget was added.
		$dashboard->getWidget('Action log');
		$this->checkLastSelectedWidgetType();

		$dashboard->cancelEditing();
	}

	/**
	 * Check last selected widget type in frontend and in DB.
	 * By default is 'Action log' type and without record in DB.
	 *
	 * @param string $type			widget type name
	 * @param string $db_type		widget type name stored in DB
	 */
	private function checkLastSelectedWidgetType($type = 'Action log', $db_type = null) {
		$dashboard = CDashboardElement::find()->one();
		$this->query('id:overlay_bg')->waitUntilNotVisible();
		$overlay = $dashboard->addWidget();
		$form = $overlay->asForm();
		$this->assertEquals($type, $form->getField('Type')->getValue());

		if ($db_type) {
			$this->assertEquals($db_type, CDBHelper::getValue("SELECT value_str FROM profiles".
					" WHERE userid=1 AND idx='web.dashbrd.last_widget_type'"));
		}
		else {
			$this->assertEquals(0, CDBHelper::getCount("SELECT * FROM profiles".
					" WHERE userid=1 AND idx='web.dashbrd.last_widget_type'"));
		}

		$overlay->close();
	}

	/**
	 * Widget type should be inherited from the one that was selected last time.
	 */
	public function testPageDashboardWidgets_checkWidgetTypeRemembering() {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=1');
		$dashboard = CDashboardElement::find()->one()->edit();
		// Opening widget configuration form for new Clock widget.
		$overlay = $dashboard->addWidget();
		$form = $overlay->asForm();
		$form->fill(['Type' => 'Clock']);
		$form->waitUntilReloaded();
		$overlay->close();
		// Check that widget type is remembered as Clock.
		$this->checkLastSelectedWidgetType('Clock', 'clock');

		// Save edit widget form without changing widget type.
		$form = $dashboard->getWidget('System information')->edit();
		$this->assertEquals('System information', $form->getField('Type')->getValue());
		$form->submit();
		$this->page->waitUntilReady();
		// Check that widget type is still remembered as Clock.
		$this->checkLastSelectedWidgetType('Clock', 'clock');

		// Opening edit widget form and change widget type.
		$form = $dashboard->getWidget('System information')->edit();
		$form->fill(['Type' => 'Data overview']);
		$overlay->close();
		// Check that widget type inherited from previous widget.
		$this->checkLastSelectedWidgetType('Data overview', 'dataover');

		$dashboard->cancelEditing();
	}

	/**
	 * Check "Problem Hosts" widget.
	 */
	public function testPageDashboardWidgets_checkProblemHostsWidget() {
		// Authorize user and open the page 'zabbix.php?action=dashboard.view&dashboardid=100'.
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=100');

		// Find dashboard element.
		$dashboard = CDashboardElement::find()->one();

		// Get dashboard widget element by widget title ('Problem hosts').
		$widget = $dashboard->getWidget('Problem hosts');
		// Check refresh interval of widget.
		$this->assertEquals(60, $widget->getRefreshInterval());

		// Get widget content as table.
		$table = $widget->getContent()->asTable();
		// Check text of the table headers.
		$this->assertSame(['Host group', 'Without problems', 'With problems', 'Total'], $table->getHeadersText());

		// Expected table values.
		$expected = [
			'Host group for tag permissions'	=> 1,
			'Zabbix servers'					=> 18,
			'ZBX6648 All Triggers'				=> 1,
			'ZBX6648 Disabled Triggers'			=> 1,
			'ZBX6648 Enabled Triggers'			=> 1
		];

		/*
		 * Index rows by column "Host group":
		 * Get table data as array where values from column 'Host group' are used as array keys.
		 */
		$data = $table->index('Host group');

		foreach ($expected as $group => $problems) {
			// Get table row by host name.
			$row = $data[$group];

			// Check the value in table.
			$this->assertEquals($problems, $row['Without problems']);
			$this->assertEquals($problems, $row['Total']);
		}
	}

	/**
	 * Check empty dashboard.
	 */
	public function testPageDashboardWidgets_checkEmptyDashboard() {
		$this->page->login()->open('zabbix.php?action=dashboard.list');

		// Create a new dashboard.
		$this->query('button:Create dashboard')->one()->click();
		// Wait until overlay dialog is visible and ready.
		$dialog = $this->query('id:overlay_dialogue')->waitUntilVisible()->asOverlayDialog()->one()->waitUntilReady();

		// Check title of a dialog.
		$this->assertEquals('Dashboard properties', $dialog->getTitle());
		// Close the dialog.
		$dialog->close();

		$dashboard = CDashboardElement::find()->one();
		// Check if dashboard is empty.
		$this->assertTrue($dashboard->isEmpty());

		// Open a new widget form.
		$overlay = $dashboard->edit()->addWidget();
		// Wait until add widget dialog is shown and close it.
		$overlay->close();

		// Check if dashboard is still empty.
		$this->assertTrue($dashboard->isEmpty());
		// Cancel dashboard editing.
		$dashboard->cancelEditing();
	}

	/**
	 * Create dashboard with clock widget.
	 */
	public function testPageDashboardWidgets_checkDashboardCreate() {
		$this->page->login()->open('zabbix.php?action=dashboard.list');

		$this->query('button:Create dashboard')->one()->click();
		$dialog = $this->query('id:overlay_dialogue')->waitUntilVisible()->asOverlayDialog()->one()->waitUntilReady();

		$this->assertEquals('Dashboard properties', $dialog->getTitle());
		$configuration = $dialog->asForm();
		// Change dashboard owner.
		$owner = $configuration->getField('Owner');
		$owner->clear();
		$owner->select('test-user');
		// Change dashboard name.
		$configuration->getField('Name')->clear()->type('Dashboard create test');
		$configuration->submit();

		$dashboard = CDashboardElement::find()->one();
		// Check the name of dashboard.
		$this->query('id:page-title-general')->waitUntilTextPresent('Dashboard create test');
		$this->assertEquals('Dashboard create test', $dashboard->getTitle());

		// Add widget.
		$overlay = $dashboard->addWidget();
		$form = $overlay->asForm();
		// Set type to "Clock".
		$form->getField('Type')->asDropdown()->select('Clock');
		// Wait until overlay is reloaded.
		$overlay->waitUntilReady();
		// Set name of widget.
		$form->getField('Name')->type('Clock test');
		$form->submit();

		// Check if widget was added.
		$widget = $dashboard->getWidget('Clock test');
		$widget->getContent()->query('class:clock')->waitUntilVisible();

		// Save dashboard.
		$dashboard->save();
		$this->page->waitUntilReady();

		// Get global message.
		$message = CMessageElement::find()->one();
		// Check if message is positive.
		$this->assertTrue($message->isGood());
		// Check message title.
		$this->assertEquals('Dashboard created', $message->getTitle());
	}

	/**
	 * Edit widget.
	 */
	public function testPageDashboardWidgets_checkProblemWidgetEdit() {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=1');

		$dashboard = CDashboardElement::find()->one()->edit();
		$widget = $dashboard->getWidget('Problems by severity');

		// Check if widget is editable.
		$this->assertEquals(true, $widget->isEditable());

		// Open widget edit form and get the form element.
		$form = $widget->edit();

		// Get "Host groups" multiselect.
		$groups = $form->getField('Host groups');

		// Select a single group.
		$groups->select('Zabbix servers');

		// Change the name of widget.
		$form->getField('Name')->clear()->type('New widget name');
		// Change show option.
		$form->getField('Show')->select('Host groups');
		// Submit the form.
		$form->submit();

		// Check if widget can be found by a new name.
		$dashboard->getWidget('New widget name');
		$table = $widget->getContent()->asTable();
		// Check if only selected host group is shown.
		$this->assertEquals(['Zabbix servers'], array_keys($table->index('Host group')));

		$dashboard->cancelEditing();
	}
}
