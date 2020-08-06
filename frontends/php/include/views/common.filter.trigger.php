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


require_once dirname(__FILE__).'/js/common.filter.trigger.js.php';

$filter = $this->data['filter'];
$config = $this->data['config'];

$filterForm = (new CFilter((new CUrl('overview.php'))->setArgument('type', 0)))
	->setProfile($data['profileIdx'])
	->setActiveTab($data['active_tab'])
	->addVar('groupid', $filter['groupId'])
	->addVar('hostid', $filter['hostId']);

$severityNames = [];
for ($severity = TRIGGER_SEVERITY_NOT_CLASSIFIED; $severity < TRIGGER_SEVERITY_COUNT; $severity++) {
	$severityNames[] = getSeverityName($severity, $config);
}

$column1 = (new CFormList())
	->addRow(_('Show'),
		(new CRadioButtonList('show_triggers', (int) $filter['showTriggers']))
			->addValue(_('Recent problems'), TRIGGERS_OPTION_RECENT_PROBLEM)
			->addValue(_('Problems'), TRIGGERS_OPTION_IN_PROBLEM)
			->addValue(_('Any'), TRIGGERS_OPTION_ALL)
			->setModern(true)
	)
	->addRow(_('Minimum severity'),
		new CComboBox('show_severity', $filter['showSeverity'], null, $severityNames)
	);

$statusChangeDays = (new CNumericBox('status_change_days', $filter['statusChangeDays'], 3, false, false, false))
	->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH);
if (!$filter['statusChange']) {
	$statusChangeDays->setAttribute('disabled', 'disabled');
}

$column1
	->addRow(_('Age less than'), [
		(new CCheckBox('status_change'))
			->setChecked($filter['statusChange'] == 1)
			->onClick('javascript: this.checked ? $("status_change_days").enable() : $("status_change_days").disable()'),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		$statusChangeDays,
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		_('days')
	])
	->addRow(_('Name'),
		(new CTextBox('txt_select', $filter['txtSelect']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
	)
	->addRow(_('Application'), [
		(new CTextBox('application', $filter['application']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CButton('application_name', _('Select')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('return PopUp("popup.generic",'.
				CJs::encodeJson([
					'srctbl' => 'applications',
					'srcfld1' => 'name',
					'dstfrm' => 'zbx_filter',
					'dstfld1' => 'application',
					'real_hosts' => '1',
					'with_applications' => '1'
				]).', null, this);'
			)
	]);

// inventory filter
$inventoryFilters = $filter['inventory'];
if (!$inventoryFilters) {
	$inventoryFilters = [
		['field' => '', 'value' => '']
	];
}
$inventoryFields = [];
foreach (getHostInventories() as $inventory) {
	$inventoryFields[$inventory['db_field']] = $inventory['title'];
}

$inventoryFilterTable = new CTable();
$inventoryFilterTable->setId('inventory-filter');
$i = 0;
foreach ($inventoryFilters as $field) {
	$inventoryFilterTable->addRow([
		new CComboBox('inventory['.$i.'][field]', $field['field'], null, $inventoryFields),
		(new CTextBox('inventory['.$i.'][value]', $field['value']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
		(new CCol(
			(new CButton('inventory['.$i.'][remove]', _('Remove')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-remove')
		))->addClass(ZBX_STYLE_NOWRAP)
	], 'form_row');

	$i++;
}
$inventoryFilterTable->addRow(
	(new CCol(
		(new CButton('inventory_add', _('Add')))
			->addClass(ZBX_STYLE_BTN_LINK)
			->addClass('element-table-add')
	))->setColSpan(2)
);

$column2 = (new CFormList())
	->addRow(_('Host inventory'), $inventoryFilterTable)
	->addRow(_('Show unacknowledged only'),
		(new CCheckBox('ack_status'))
			->setChecked($filter['ackStatus'] == 1)
			->setUncheckedValue(0)
	)
	->addRow(_('Show suppressed problems'),
		(new CCheckBox('show_suppressed'))->setChecked($filter['show_suppressed'] == ZBX_PROBLEM_SUPPRESSED_TRUE)
	);

$filterForm->addFilterTab(_('Filter'), [$column1, $column2]);

return $filterForm;
