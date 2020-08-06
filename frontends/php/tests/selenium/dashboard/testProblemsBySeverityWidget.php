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

require_once dirname(__FILE__).'/../../include/CWebTest.php';

/**
 * @backup widget
 * @backup profiles
 */
class testProblemsBySeverityWidget extends CWebTest {

	/*
	 * SQL query to get widget and widget_field tables to compare hash values, but without widget_fieldid
	 * because it can change.
	 */
	private $sql = 'SELECT wf.widgetid, wf.type, wf.name, wf.value_int, wf.value_str, wf.value_groupid, wf.value_hostid,'.
			' wf.value_itemid, wf.value_graphid, wf.value_sysmapid, w.widgetid, w.dashboardid, w.type, w.name, w.x, w.y,'.
			' w.width, w.height'.
			' FROM widget_field wf'.
			' INNER JOIN widget w'.
			' ON w.widgetid=wf.widgetid ORDER BY wf.widgetid, wf.name, wf.value_int, wf.value_groupid';

	public function getCreateWidgetData() {
		return [
			// Create a widget with default values.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity'
					],
					'check' => [
						'pop-up' => true,
						'disabled' => true
					]
				]
			],
			// Create a widget with selected 'show latest values' option and removed 'show timeline' option.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Show operational data and timeline',
						'Refresh interval' => 'Default (1 minute)',
						'Show operational data' => 'Separately',
						'Show timeline' => false
					],
					'check' => [
						'pop-up' => true
					]
				]
			],
			// Create a widget that shows only hosts with problems with problem filtering by their severity.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Show only hosts with problems filtered by severity',
						'Refresh interval' => '1 minute',
						'Hide groups without problems' => true,
						'Severity' => ['Disaster', 'Warning', 'Information']
					],
					'expected' => [
						'Group to check Overview' => [
							'Disaster' => '1',
							'Warning' => '1',
							'Information' => '1'
						],
						'Zabbix servers' => [
							'Warning' => '5'
						]
					]
				]
			],
			// Create a widget with selected 'show suppressed problems' option.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Show suppressed problems',
						'Show suppressed problems' => true
					]
				]
			],
			// Create a widget with selected 'show suppressed problems' option that shows only hosts with problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Show only for hosts with problems + show suppressed problems',
						'Show suppressed problems' => true,
						'Hide groups without problems' => true,
						'Problem display' => 'Separated'
					],
					'expected' => [
						'Another group to check Overview' => [
							'Average' => "0 of 1"
						],
						'Group to check Overview' => [
							'Disaster' => "1\nof 1",
							'High' => "1\nof 1",
							'Average' => "1\nof 2",
							'Warning' => "1\nof 1",
							'Information' => "0 of 1",
							'Not classified' => "1\nof 1"
						],
						'Group to check triggers filtering' => [
							'Average' => "1\nof 1"
						],
						'Host group for suppression' => [
							'Average' => "1\nof 1"
						],
						'Host group for tag permissions' => [
							'Not classified' => "2\nof 2"
						],
						'Zabbix servers' => [
							'Average' => "1\nof 1",
							'Warning' => "5\nof 5"
						]
					]
				]
			],
			// Create a widget with 'Zabbix servers' and 'Another group to check Overview' host group problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Show only "Zabbix servers" and "Another group to check Overview" problems',
						'Host groups' => ['Zabbix servers', 'Another group to check Overview'],
						'Show operational data' => 'With problem name'
					],
					'expected' => [
						'Another group to check Overview' => [
							'Average' => '1'
						],
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					],
					'check' => [
						'pop-up' => true
					]
				]
			],
			// Create a widget that excludes several host groups.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Exclude "Zabbix servers"',
						'Exclude host groups' => ['Zabbix servers', 'Empty group', 'Group to check Overview']
					]
				]
			],
			// Create a widget that shows only '1_Hos_to_check_Monitoring_Overview' host problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Display only "1_Host_to_check_Monitoring_Overview"',
						'Problem display' => 'Separated',
						'Severity' => ['Disaster', 'High', 'Average', 'Warning', 'Information', 'Not classified'],
						'Hosts' => [
							'values' => ['1_Host_to_check_Monitoring_Overview'],
							'context' => 'Group to check Overview'
						]
					],
					'expected' => [
						'Group to check Overview' => [
							'Disaster' => "1\nof 1",
							'High' => "1\nof 1",
							'Average' => "1\nof 1",
							'Warning' => "1\nof 1",
							'Information' => "0 of 1",
							'Not classified' => "1\nof 1"
						]
					]
				]
			],
			// Create a widget that shows only 'Third test trigger with tag priority' problem.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Display only "Third test trigger with tag priority" problem',
						'Problem' => 'Third test trigger with tag priority',
						'Problem display' => 'Separated'
					],
					'expected' => [
						'Zabbix servers' => [
							'Warning' => "1\nof 1"
						]
					]
				]
			],
			// Create a widget that shows only problems that contain 'Test trigger with tag'.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Display problems containing "Test trigger with tag" string',
						'Problem' => 'Test trigger with tag'
					],
					'expected' => [
						'Zabbix servers' => [
							'Warning' => '5'
						]
					]
				]
			],
			// Create a widget that shows only unaknowledged problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Display only unacknowledged problems',
						'Problem display' => 'Unacknowledged only'
					],
					'expected' => [
						'Group to check Overview' => [
							'Disaster' => '1',
							'High' => '1',
							'Average' => '1',
							'Warning' => '1',
							'Not classified' => '1'
						],
						'Group to check triggers filtering' => [
							'Average' => '1'
						],
						'Host group for tag permissions' => [
							'Not classified' => '2'
						],
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					]
				]
			],
			// Totals: Create a widget with default values.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Widget with default parameters',
						'Show' => 'Totals'
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '5',
						'Warning' => '6',
						'Information' => '1',
						'Not classified' => '3'
					],
					'check' => [
						'pop-up' => true,
						'disabled' => true
					]
				]
			],
			/*
			 * Totals: Create a widget with selected 'show supprossed problems and ''show operational data' option
			 * and removed 'show timeline' option.
			 */
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Show suppressed problems with operational data and without timeline',
						'Refresh interval' => 'Default (1 minute)',
						'Show' => 'Totals',
						'Show suppressed problems' => true,
						'Show operational data' => 'Separately',
						'Show timeline' => false
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '6',
						'Warning' => '6',
						'Information' => '1',
						'Not classified' => '3'
					],
					'check' => [
						'pop-up' => true
					]
				]
			],
			// Totals: Create a widget that shows only problems with Disaster, Warning and Information severities with Vertical layout.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Show only hosts with problems filtered by severity',
						'Refresh interval' => '1 minute',
						'Show' => 'Totals',
						'Layout' => 'Vertical',
						'Severity' => ['Information', 'Warning', 'Disaster']
					],
					'expected' => [
						'Disaster' => '1',
						'Warning' => '6',
						'Information' => '1'
					]
				]
			],
			// Totals: Create a widget with 'Zabbix servers' and 'Another group to check Overview' host group problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Show only "Zabbix servers" and "Another group to check Overview" problems',
						'Show' => 'Totals',
						'Layout' => 'Horizontal',
						'Host groups' => ['Zabbix servers', 'Another group to check Overview']
					],
					'expected' => [
						'Average' => '2',
						'Warning' => '5'
					]
				]
			],
			// Totals: Create a widget that excludes several host groups and displays unacknowledged problems separately.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Exclude "Zabbix servers" with separated problem display',
						'Show' => 'Totals',
						'Layout' => 'Vertical',
						'Problem display' => 'Separated',
						'Exclude host groups' => ['Zabbix servers', 'Group to check triggers filtering']
					],
					'expected' => [
						'Disaster' => "1\nof 1",
						'High' => "1\nof 1",
						'Average' => "1\nof 3",
						'Warning' => "1\nof 1",
						'Information' => "0 of 1",
						'Not classified' => "3\nof 3"
					]
				]
			],
			// Totals: Create a widget that shows only '1_Hos_to_check_Monitoring_Overview' host problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Display only "1_Host_to_check_Monitoring_Overview"',
						'Show' => 'Totals',
						'Severity' => ['Not classified', 'Information', 'Warning', 'Average', 'High', 'Disaster'],
						'Hosts' => [
							'values' => ['1_Host_to_check_Monitoring_Overview'],
							'context' => 'Group to check Overview'
						]
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '1',
						'Warning' => '1',
						'Information' => '1',
						'Not classified' => '1'
					]
				]
			],
			// Totals: Create a widget that shows only problems that contain 'Test trigger with tag'.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Display problems containing "Test trigger with tag" string',
						'Show' => 'Totals',
						'Problem' => 'Test trigger with tag'
					],
					'expected' => [
						'Warning' => '5'
					]
				]
			],
			// Totals: Create a widget that shows only unaknowledged problems.
			[
				[
					'fields' => [
						'Type' => 'Problems by severity',
						'Name' => 'Totals: Display only unacknowledged problems',
						'Show' => 'Totals',
						'Layout' => 'Vertical',
						'Problem display' => 'Unacknowledged only'
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '3',
						'Warning' => '6',
						'Information' => '0',
						'Not classified' => '3'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getCreateWidgetData
	 */
	public function testProblemsBySeverityWidget_Create($data) {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=104');
		$dashboard = CDashboardElement::find()->one();
		$old_widget_count = $dashboard->getWidgets()->count();

		// Add a widget.
		$dialogue = $dashboard->edit()->addWidget();
		$form = $dialogue->asForm();
		$header = CTestArrayHelper::get($data['fields'], 'Name', 'Problems by severity');
		$this->fillFormAndSaveDashboard($dashboard, $form, $data, $header);
		$widget = $dashboard->getWidget($header);
		// Check that Dashboard has been saved and that there are no errors in the widget
		$this->checkDashboardMessage();
		$this->assertEquals($old_widget_count + 1, $dashboard->getWidgets()->count());
		$show = CTestArrayHelper::get($data['fields'], 'Show', 'Host groups');
		if ($show === 'Host groups') {
			$this->checkWidgetContent($data, $widget);
		}
		else {
			$this->checkTotalsWidgetContent($data, $widget);
		}
		// Check the content of details hintbox for Host "ЗАББИКС Сервер" and severity "Average" if needed.
		if (CTestArrayHelper::get($data, 'check.pop-up', false)) {
			$this->checkPopupContent($data, $widget, $show);
		}
	}

	public function getUpdateWidgetData() {
		return [
			// Update widget to have a default name.
			[
				[
					'fields' => [
						'Name' => ''
					]
				]
			],
			// Hide host groups without problems and remove timeline.
			[
				[
					'fields' => [
						'Name' => 'Hide timeline and groupt without problems',
						'Refresh interval' => 'Default (1 minute)',
						'Hide groups without problems' => true,
						'Show operational data' => 'With problem name',
						'Show timeline' => false
					],
					'expected' => [
						'Another group to check Overview' => [
							'Average' => '1'
						],
						'Group to check Overview' => [
							'Disaster' => '1',
							'High' => '1',
							'Average' => '2',
							'Warning' => '1',
							'Information' => '1',
							'Not classified' => '1'
						],
						'Group to check triggers filtering' => [
							'Average' => '1'
						],
						'Host group for tag permissions' => [
							'Not classified' => '2'
						],
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					],
					'check' => [
						'pop-up' => true
					]
				]
			],
			// Show only average problems including suppressed ones, problem display - separated, exclude hostgroups without problems.
			[
				[
					'fields' => [
						'Name' => 'Show only average problems including suppressed ones',
						'Hide groups without problems' => true,
						'Show suppressed problems' => true,
						'Problem display' => 'Separated',
						'Severity' => ['Average']
					],
					'expected' => [
						'Another group to check Overview' => [
							'Average' => "0 of 1"
						],
						'Group to check Overview' => [
							'Average' => "1\nof 2"
						],
						'Group to check triggers filtering' => [
							'Average' => "1\nof 1"
						],
						'Host group for suppression' => [
							'Average' => "1\nof 1"
						],
						'Zabbix servers' => [
							'Average' => "1\nof 1"
						]
					]
				]
			],
			// Update widget to display only unaknowledged problems and to show latest values.
			[
				[
					'fields' => [
						'Name' => 'Display only unacknowledged problems',
						'Problem display' => 'Unacknowledged only',
						'Show operational data' => 'Separately'
					],
					'expected' => [
						'Group to check Overview' => [
							'Disaster' => '1',
							'High' => '1',
							'Average' => '1',
							'Warning' => '1',
							'Not classified' => '1'
						],
						'Group to check triggers filtering' => [
							'Average' => '1'
						],
						'Host group for tag permissions' => [
							'Not classified' => '2'
						],
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					],
					'check' => [
						'pop-up' => true
					]
				]
			],
			// Update the widget to return only "Group to check Overview" hostgroup problems.
			[
				[
					'fields' => [
						'Name' => 'Show only problems of hostgroup Group to check Overview',
						'Host groups' => 'Group to check Overview'
					],
					'expected' => [
						'Group to check Overview' => [
							'Disaster' => '1',
							'High' => '1',
							'Average' => '2',
							'Warning' => '1',
							'Information' => '1',
							'Not classified' => '1'
						]
					]
				]
			],
			// Empty widget output: return problems of 'Zabbix servers' hostroup and a host that doesn't belong to it.
			[
				[
					'fields' => [
						'Name' => 'Return "Zabbix servers" and "Another group to check Overview" problems',
						'Host groups' => ['Zabbix servers'],
						'Hosts' => [
							'values' => ['Empty host'],
							'context' => 'Empty group'
						]
					],
					'expected' => [],
					'check' => [
						'empty' => true
					]
				]
			],
			// Update widget to exclude 'Group to check Overview' host group.
			[
				[
					'fields' => [
						'Name' => 'Exclude "Group to check Overview"',
						'Exclude host groups' => ['Group to check Overview']
					],
					'expected' => [
						'Another group to check Overview' => [
							'Average' => '1'
						],
						'Group to check triggers filtering' => [
							'Average' => '1'
						],
						'Host group for tag permissions' =>[
							'Not classified' => '2'
						],
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					]
				]
			],
			// Update widget to return problems of 'ЗАББИКС Сервер' host.
			[
				[
					'fields' => [
						'Name' => 'Return "ЗАББИКС Сервер" problems',
						'Hosts' => [
							'values' => ['ЗАББИКС Сервер'],
							'context' => 'Zabbix servers'
						]
					],
					'expected' => [
						'Zabbix servers' => [
							'Average' => '1',
							'Warning' => '5'
						]
					]
				]
			],
			// Empty widget output: problems of "ЗАББИКС Сервер" host with excluded "Zabbix servers" hostgroup.
			[
				[
					'fields' => [
						'Name' => 'Display ЗАББИКС Сервер problems with excluded "Zabbix servers"',
						'Exclude host groups' => ['Zabbix servers'],
						'Hosts' => [
							'values' => ['ЗАББИКС Сервер'],
							'context' => 'Zabbix servers'
						],
						'Severity' => ['Disaster', 'High', 'Average', 'Warning', 'Information', 'Not classified']
					],

					'expected' => [],
					'check' => [
						'empty' => true
					]
				]
			],
			// Update widget to show a non existing problem.
			[
				[
					'fields' => [
						'Name' => 'No problems should be returned',
						'Problem' => 'Please place Your problem name here'
					],
					'expected' => []
				]
			],
			// Update widget to show only warning and information problems that contain '_trigger_'.
			[
				[
					'fields' => [
						'Name' => 'Display only warning and information problems containing "_trigger_',
						'Problem' => '_trigger_',
						'Severity' => ['Warning', 'Information']
					],
					'expected' => [
						'Group to check Overview' => [
							'Warning' => '1',
							'Information' => '1'
						]
					]
				]
			],
			// Show only average problems including suppressed ones, problem display - separated.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: separately show average problems including suppressed ones',
						'Show suppressed problems' => true,
						'Problem display' => 'Separated',
						'Severity' => ['Average']
					],
					'expected' => [
						'Average' => "4\nof 6"
					]
				]
			],
			// Update widget to display only unaknowledged problems and to show latest values without timeline.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Display only unacknowledged problems with operational data withour timeline',
						'Problem display' => 'Unacknowledged only',
						'Show operational data' => 'Separately',
						'Show timeline' => false

					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '3',
						'Warning' => '6',
						'Information' => '0',
						'Not classified' => '3'
					],
					'check' => [
						'pop-up' => true,
						'rows' => 3
					]
				]
			],
			// Update the widget to return only "Group to check Overview" hostgroup problems.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Show only problems of hostgroup Group to check Overview',
						'Host groups' => 'Group to check Overview'
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '2',
						'Warning' => '1',
						'Information' => '1',
						'Not classified' => '1'
					]
				]
			],
			// Empty widget output: return problems of 'Zabbix servers' hostroup and a host that doesn't belong to it.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Return "Zabbix servers" and "Another group to check Overview" problems',
						'Host groups' => ['Zabbix servers'],
						'Hosts' => [
							'values' => ['Empty host'],
							'context' => 'Empty group'
						]
					],
					'expected' => []
				]
			],
			// Update widget to exclude 'Group to check Overview' host group.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Exclude "Group to check Overview"',
						'Exclude host groups' => ['Group to check Overview']
					],
					'expected' => [
						'Average' => '3',
						'Warning' => '5',
						'Not classified' => '2'
					]
				]
			],
			// Update widget to return problems of 'ЗАББИКС Сервер' host.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Return "ЗАББИКС Сервер" problems',
						'Hosts' => [
							'values' => ['ЗАББИКС Сервер'],
							'context' => 'Zabbix servers'
						]
					],
					'expected' => [
						'Average' => '1',
						'Warning' => '5'
					]
				]
			],
			// Empty widget output: problems of "ЗАББИКС Сервер" host with excluded "Zabbix servers" hostgroup.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Display ЗАББИКС Сервер problems with excluded "Zabbix servers"',
						'Exclude host groups' => ['Zabbix servers'],
						'Severity' => ['Not classified', 'Information', 'Warning', 'Average', 'High', 'Disaster'],
						'Hosts' => [
							'values' => ['ЗАББИКС Сервер'],
							'context' => 'Zabbix servers'
						]
					],
					'expected' => []
				]
			],
			// Update widget to show a non existing problem.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: No problems should be returned',
						'Problem' => 'Please place Your problem name here'
					],
					'expected' => []
				]
			],
			// Update widget to show only warning and information problems that contain '_trigger_'.
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Totals: Display only warning and information problems containing "_trigger_',
						'Problem' => '_trigger_',
						'Severity' => ['Information', 'Warning']
					],
					'expected' => [
						'Warning' => '1',
						'Information' => '1'
					]
				]
			],
			// Change "Show" from "Totals" to "Host groups"
			[
				[
					'widget to update' => 'Totals reference widget',
					'fields' => [
						'Name' => 'Changing "show" from "Totals" to "Host groups"',
						'Show' => 'Host groups'
					],
					'check' => [
						'disabled' => true
					]
				]
			],
			// Change "Show" from "Host groups" to "Totals"
			[
				[

					'fields' => [
						'Name' => 'Changing "show" from "Host groups" to "Totals"',
						'Show' => 'Totals'
					],
					'expected' => [
						'Disaster' => '1',
						'High' => '1',
						'Average' => '5',
						'Warning' => '6',
						'Information' => '1',
						'Not classified' => '3'
					],
					'check' => [
						'disabled' => true
					]
				]
			]
		];
	}

	/**
	 * @backup widget
	 * @dataProvider getUpdateWidgetData
	 */
	public function testProblemsBySeverityWidget_Update($data) {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=104');
		$dashboard = CDashboardElement::find()->one();
		$dashboard->edit();
		// Select the widget to update
		if (CTestArrayHelper::get($data, 'widget to update', 'Reference widget') === 'Reference widget') {
			$form = $dashboard->getWidget('Reference widget')->edit();
		}
		else {
			$form = $dashboard->getWidget($data['widget to update'])->edit();
		}

		// Attempt to update the widget.
		$header = ($data['fields']['Name'] === '') ? 'Problems by severity' : $data['fields']['Name'];

		$this->fillFormAndSaveDashboard($dashboard, $form, $data, $header);
		$widget = $dashboard->getWidget($header);
		// Check that Dashboard has been saved and that there are no errors in the widget
		$this->checkDashboardMessage();

		if ((CTestArrayHelper::get($data, 'widget to update', 'Reference widget') === 'Reference widget'
				&& CTestArrayHelper::get($data['fields'], 'Show', 'Host groups') === 'Host groups')
				|| (CTestArrayHelper::get($data, 'widget to update', 'Reference widget') === 'Totals reference widget'
				&& CTestArrayHelper::get($data['fields'], 'Show', 'Totals') === 'Host groups')) {
			$this->checkWidgetContent($data, $widget);
			if (CTestArrayHelper::get($data, 'check.pop-up', false)) {
				$this->checkPopupContent($data, $widget, 'Host groups');
			}
		}
		else {
			$this->checkTotalsWidgetContent($data, $widget);
			if (CTestArrayHelper::get($data, 'check.pop-up', false)) {
				$this->checkPopupContent($data, $widget, 'Totals');
			}
		}
	}

	public function testProblemsBySeverityWidget_SimpleUpdate() {
		$initial_values = CDBHelper::getHash($this->sql);

		// Open a dashboard widget and then save it without applying any changes
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=104');
		$dashboard = CDashboardElement::find()->one();
		$dashboard->edit();
		$form = $dashboard->getWidget('Reference widget')->edit();
		$form->submit();
		$this->query('id:overlay_bg')->waitUntilNotVisible();

		$widget = $dashboard->getWidget('Reference widget');
		$widget->query('class:preloader')->waitUntilNotPresent();
		$dashboard->save();

		// Check that Dashboard has been saved and that there are no changes made to the widgets.
		$this->checkDashboardMessage();
		$this->assertEquals($initial_values, CDBHelper::getHash($this->sql));
	}

	public function getCancelActionsData() {
		return [
			// Cancel update widget.
			[
				[
					'existing_widget' => 'Reference widget',
					'save_widget' => true,
					'save_dashboard' => false
				]
			],
			[
				[
					'existing_widget' => 'Reference widget',
					'save_widget' => false,
					'save_dashboard' => true
				]
			],
			// Cancel create widget.
			[
				[
					'save_widget' => true,
					'save_dashboard' => false
				]
			],
			[
				[
					'save_widget' => false,
					'save_dashboard' => true
				]
			]
		];
	}

	/**
	 * @dataProvider getCancelActionsData
	 */
	public function testProblemsBySeverityWidget_Cancel($data) {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=104');
		$dashboard = CDashboardElement::find()->one()->edit();

		// Start updating or creating a widget.
		if (CTestArrayHelper::get($data, 'existing_widget', false)) {
			$widget = $dashboard->getWidget($data['existing_widget']);
			$form = $widget->edit();
		}
		else {
			$overlay = $dashboard->addWidget();
			$form = $overlay->asForm();
			$form->getField('Type')->fill('Problems by severity');
			$widget = $dashboard->getWidgets()->last();
		}
		$form->getField('Name')->fill('Widget to be cancelled');

		// Save or cancel widget.
		if (CTestArrayHelper::get($data, 'save_widget', false)) {
			$form->submit();
			$this->page->waitUntilReady();
			// Check that changes took place on the unsaved dashboard.
			$this->assertTrue($dashboard->getWidget('Widget to be cancelled')->isVisible());
		}
		else {
			$this->query('button:Cancel')->one()->click();
			// Check that widget changes wasn't took place after pressing "Cancel".
			if (CTestArrayHelper::get($data, 'existing_widget', false)) {
				$this->assertNotEquals('Widget to be cancelled', $widget->waitUntilReady()->getHeaderText());
			}
			else {
				// If test fails and widget isn't canceled, need to wait until widget appears on the dashboard.
				sleep(5);
				if ($widget->getID() !== $dashboard->getWidgets()->last()->getID()) {
					$this->fail('New widget was added after pressing "Cancel"');
				}
			}
		}
		// Save or cancel dashboard update.
		if (CTestArrayHelper::get($data, 'save_dashboard', false)) {
			$dashboard->save();
		}
		else {
			$dashboard->cancelEditing();
		}
		// Confirm that no changes were made to the widget.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public function testProblemsBySeverityWidget_Delete() {
		foreach (['Reference PBS widget to delete', 'Totals reference PBS widget to delete'] as $name) {
			$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid=104');
			$dashboard = CDashboardElement::find()->one()->edit();
			$widget = $dashboard->getWidget($name);
			$dashboard->deleteWidget($name);
			$this->page->waitUntilReady();
			$dashboard->save();
			// Check that Dashboard has been saved
			$this->checkDashboardMessage();
			// Confirm that widget is not present on dashboard.
			$this->assertEquals(0, $dashboard->query('xpath:.//div[contains(@class, "dashbrd-grid-widget-head")]/h4[text()='.
					CXPathHelper::escapeQuotes($name).']')->count());
			$widget_sql = 'SELECT * FROM widget_field wf LEFT JOIN widget w ON w.widgetid=wf.widgetid'.
					' WHERE w.name='.zbx_dbstr($name);
			$this->assertEquals(0, CDBHelper::getCount($widget_sql));
		}
	}

	private function fillFormAndSaveDashboard($dashboard, $form, $data, $header) {
		$form->fill($data['fields']);
		if (CTestArrayHelper::get($data, 'check.disabled', false)) {
			if (CTestArrayHelper::get($data['fields'], 'Show', 'Host groups') === 'Totals') {
				$this->assertTrue($form->getField('Layout')->isEnabled());
				$this->assertTrue($form->getField('Hide groups without problems')->isEnabled(false));
			}
			else {
				$this->assertTrue($form->getField('Hide groups without problems')->isEnabled());
				$this->assertTrue($form->getField('Layout')->isEnabled(false));
			}
		}
		$form->submit();
		$this->query('id:overlay_bg')->waitUntilNotVisible();
		$widget = $dashboard->getWidget($header);
		$widget->query('class:preloader')->waitUntilNotPresent();
		$dashboard->save();
	}

	private function checkWidgetContent($data, $widget) {
		$table = $widget->getContent()->asTable();
		// Defining expected results in case if no filterint is applied.
		$default_values = [
			'values' => [
				'Another group to check Overview' => [
					'Average' => '1'
				],
				'Group to check Overview' => [
					'Disaster' => '1',
					'High' => '1',
					'Average' => '2',
					'Warning' => '1',
					'Information' => '1',
					'Not classified' => '1'
				],
				'Group to check triggers filtering' => [
					'Average' => '1'
				],
				'Host group for tag permissions' => [
					'Not classified' => '2'
				],
				'Zabbix servers' => [
					'Average' => '1',
					'Warning' => '5'
				],
				'Host group for suppression' => [
					'Average' => '1'
				]
			],
			'Severity' => ['Disaster', 'High', 'Average', 'Warning', 'Information', 'Not classified']
		];

		// Check that only chosen severities are returned in the output if 'Severity' filter is used.
		$table_headers = CTestArrayHelper::get($data, 'fields.Severity', false) ? $data['fields']['Severity'] : $default_values['Severity'];
		array_unshift($table_headers, 'Host group');
		$this->assertEquals($table_headers, $table->getHeadersText());

		// Check that nothing is returned in the widget if such outcome is expected.
		if (CTestArrayHelper::get($data, 'check.empty', false)) {
			$this->assertTrue($widget->query('class:nothing-to-show')->one()->isTextPresent('No data found.'));

			return;
		}

		$content = $table->index('Host group');
		$expected = CTestArrayHelper::get($data, 'expected', $default_values['values']);

		// Remove the suppressed problem from expected results if flag 'Show suppressed problems' is set.
		if (CTestArrayHelper::get($data['fields'], 'Show suppressed problems', false) === false) {
			unset($expected['Host group for suppression']);
		}

		// Remove the host groups included in 'Exclude host groups' from expected results
		if (CTestArrayHelper::get($data['fields'], 'Exclude host groups', false)) {
			foreach ($data['fields']['Exclude host groups'] as $excluded_group) {
				unset($expected[$excluded_group]);
			}
		}

		// Check that only selected host groups are returned when 'Hide groups without values', 'Host groups' or 'Hosts' are specified.
		if (CTestArrayHelper::get($data['fields'], 'Hide groups without problems', false)
				|| array_key_exists('Host groups', $data['fields']) || array_key_exists('Hosts', $data['fields'])) {
			$this->assertEquals(count($expected), count($content));
		}

		// Check the values where result is expected and check that only expected table cells have values.
		foreach ($content as $row => $fields) {
			foreach ($fields as $column => $value) {
				if ($column === 'Host group') {
					continue;
				}
				elseif (array_key_exists($row, $expected) && array_key_exists($column, $expected[$row])) {
					$this->assertEquals($value, $expected[$row][$column]);
				}
				else {
					$this->assertEquals($value, '');
				}
			}
		}
	}

	private function checkTotalsWidgetContent($data, $widget) {
		// Verify that layout is correct.
		if (CTestArrayHelper::get($data['fields'], 'Layout', 'Horizontal') === 'Horizontal') {
			$this->assertEquals($widget->query('class:totals-list-horizontal')->count(), 1);
			$this->assertEquals($widget->query('class:totals-list-vertical')->count(), 0);
		}
		else {
			$this->assertEquals($widget->query('class:totals-list-horizontal')->count(), 0);
			$this->assertEquals($widget->query('class:totals-list-vertical')->count(), 1);
		}

		$results = [];
		$classes = [
			'Disaster' => 'disaster-bg',
			'High' => 'high-bg',
			'Average' => 'average-bg',
			'Warning' => 'warning-bg',
			'Information' => 'info-bg',
			'Not classified' => 'na-bg'
		];

		// Get the count of problems for each severity that is returned by the widget.
		foreach ($classes as $key => $class) {
			$xpath = 'xpath:.//div[@class='.CXPathHelper::escapeQuotes($class).']/span';
			if (array_key_exists('Severity', $data['fields']) && !array_key_exists($key, $data['fields']['Severity'])) {
				continue;
			}
			$results[$key] = $widget->query($xpath)->one()->getText();
		}

		foreach ($results as $severity => $value) {
			if (array_key_exists($severity, $data['expected'])) {
				$this->assertEquals($data['expected'][$severity], $value);
			}
			else {
				$this->assertEquals(0, $value);
			}
		}
	}

	/*
	 * This function checks problem details hintbox content for Host "ЗАББИКС Сервер" and severity "Average".
	 * Only the number of problems and the values for one speciffic problem are checked.
	 */
	private function checkPopupContent($data, $widget, $show){
		$expected_popup = [
			'fields' => [
				'Time' => '2018-10-23 12:33:48',
				'Host' => 'ЗАББИКС Сервер',
				'Problem' => 'Test trigger to check tag filter on problem page',
				'Ack' => 'No'
			],
			'Tags' => ['Database', 'Service: abc', 'service: abcdef'],
			'rows' => [
				'Host groups' => 1,
				'Totals' => 6 // 5 problems + 1 time period (with timeline) or 6 problems (without timeline but with suppressed problem)
			]
		];

		$rows_count = (CTestArrayHelper::get($data, 'check.rows', false))
				? CTestArrayHelper::get($data['check'], 'rows')
				: CTestArrayHelper::get($expected_popup['rows'], $show);

		// Open the pop-up for severity "Average"
		if ($show === 'Host groups') {
			$table = $widget->getContent()->asTable();
			$hostgroup_row = $table->findRow('Host group', 'Zabbix servers');
			$hostgroup_row->query('xpath:.//td[@class="average-bg"]/span/a')->one()->click();
		}
		else {
			$widget->query('xpath:.//div[@class="average-bg"]//a[@data-hintbox-static="1"]')->one()->click();
		}
		$popup = $this->query('xpath://div[@class="overlay-dialogue"]//table')->asTable()->one();
		$this->assertEquals($rows_count, $popup->getRows()->count());

		$row = $popup->findRow('Problem', $expected_popup['fields']['Problem'])->asTableRow();
		foreach ($expected_popup['fields'] as $name => $value) {
			$this->assertEquals($value, $row->getColumn($name)->getText());
		}
		foreach ($expected_popup['Tags'] as $tag) {
			$tag_array = $row->getColumn('Tags')->getText();
			$this->assertContains($tag, $tag_array);
		}
		if (CTestArrayHelper::get($data['fields'], 'Show operational data', 'None') === 'Separately') {
			$this->assertEquals('*UNKNOWN*', $row->getColumn('Operational data')->getText());
		}
		else {
			$this->assertFalse($row->getColumn('Operational data')->isValid());
		}
		if (CTestArrayHelper::get($data['fields'], 'Show timeline', true)) {
			$this->assertEquals($rows_count, $popup->query('xpath:.//td[@class="timeline-date"]')->all()->count());
		}
	}

	/*
	 * Check dashboard update message.
	 */
	private function checkDashboardMessage() {
		$message = CMessageElement::find()->waitUntilVisible()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Dashboard updated', $message->getTitle());
	}
}
