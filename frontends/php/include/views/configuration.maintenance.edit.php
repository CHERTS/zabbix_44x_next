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


require_once dirname(__FILE__).'/js/configuration.maintenance.edit.js.php';

$widget = (new CWidget())->setTitle(_('Maintenance periods'));

// create form
$maintenanceForm = (new CForm())
	->setName('maintenanceForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $this->data['form']);
if (isset($this->data['maintenanceid'])) {
	$maintenanceForm->addVar('maintenanceid', $this->data['maintenanceid']);
}

/*
 * Maintenance tab
 */
$maintenanceFormList = (new CFormList('maintenanceFormList'))
	->addRow(
		(new CLabel(_('Name'), 'mname'))->setAsteriskMark(),
		(new CTextBox('mname', $this->data['mname']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
			->setAttribute('maxlength', DB::getFieldLength('maintenances', 'name'))
	)
	->addRow((new CLabel(_('Maintenance type'), 'maintenance_type')),
		(new CRadioButtonList('maintenance_type', (int) $data['maintenance_type']))
			->addValue(_('With data collection'), MAINTENANCE_TYPE_NORMAL)
			->addValue(_('No data collection'), MAINTENANCE_TYPE_NODATA)
			->setModern(true)
	)
	// Show date and time in shorter format without seconds.
	->addRow((new CLabel(_('Active since'), 'active_since'))->setAsteriskMark(),
		(new CDateSelector('active_since', $data['active_since']))
			->setDateFormat(ZBX_DATE_TIME)
			->setPlaceholder(_('YYYY-MM-DD hh:mm'))
			->setAriaRequired()
	)
	->addRow((new CLabel(_('Active till'), 'active_till'))->setAsteriskMark(),
		(new CDateSelector('active_till', $data['active_till']))
			->setDateFormat(ZBX_DATE_TIME)
			->setPlaceholder(_('YYYY-MM-DD hh:mm'))
			->setAriaRequired()
	);

$maintenanceFormList->addRow(_('Description'),
	(new CTextArea('description', $this->data['description']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
);

/*
 * Maintenance period tab
 */
$maintenancePeriodFormList = new CFormList('maintenancePeriodFormList');
$maintenance_period_table = (new CTable())
	->setAttribute('style', 'width: 100%;')
	->setHeader([_('Period type'), _('Schedule'), _('Period'), _('Action')])
	->setId('maintenance_periods')
	->setAriaRequired();

foreach ($data['timeperiods'] as $id => $timeperiod) {
	$maintenance_period_table->addRow([
		(new CCol(timeperiod_type2str($timeperiod['timeperiod_type'])))->addClass(ZBX_STYLE_NOWRAP),
		($timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_ONETIME)
			? $timeperiod['start_date']
			: schedule2str($timeperiod),
		(new CCol(zbx_date2age(0, $timeperiod['period'])))->addClass(ZBX_STYLE_NOWRAP),
		(new CCol(
			new CHorList([
				(new CSimpleButton(_('Edit')))
					->onClick('javascript: submitFormWithParam('.
						'"'.$maintenanceForm->getName().'", "edit_timeperiodid['.$id.']", "1"'.
					');')
					->addClass(ZBX_STYLE_BTN_LINK),
				(new CSimpleButton(_('Remove')))
					->onClick('javascript: submitFormWithParam('.
						'"'.$maintenanceForm->getName().'", "del_timeperiodid['.$id.']", "1"'.
					');')
					->addClass(ZBX_STYLE_BTN_LINK)
			])
		))->addClass(ZBX_STYLE_NOWRAP)
	]);
	if (isset($timeperiod['timeperiodid'])) {
		$maintenanceForm->addVar('timeperiods['.$id.'][timeperiodid]', $timeperiod['timeperiodid']);
	}
	$maintenanceForm
		->addVar('timeperiods['.$id.'][timeperiod_type]', $timeperiod['timeperiod_type'])
		->addVar('timeperiods['.$id.'][every]', $timeperiod['every'])
		->addVar('timeperiods['.$id.'][month]', $timeperiod['month'])
		->addVar('timeperiods['.$id.'][dayofweek]', $timeperiod['dayofweek'])
		->addVar('timeperiods['.$id.'][day]', $timeperiod['day'])
		->addVar('timeperiods['.$id.'][start_time]', $timeperiod['start_time'])
		->addVar('timeperiods['.$id.'][start_date]', $timeperiod['start_date'])
		->addVar('timeperiods['.$id.'][period]', $timeperiod['period']);
}

$periodsDiv = (new CDiv($maintenance_period_table))
	->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
	->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;');

if (!isset($_REQUEST['new_timeperiod'])) {
	$periodsDiv->addItem(
		(new CSimpleButton(_('New')))
			->onClick('javascript: submitFormWithParam("'.$maintenanceForm->getName().'", "new_timeperiod", "1");')
			->addClass(ZBX_STYLE_BTN_LINK)
	);
}
$maintenancePeriodFormList->addRow(
	(new CLabel(_('Periods'), $maintenance_period_table->getId()))->setAsteriskMark(), $periodsDiv
);

if ($data['new_timeperiod']) {
	if (is_array($data['new_timeperiod']) && array_key_exists('id', $data['new_timeperiod'])) {
		$save_label = _('Update');
	}
	else {
		$save_label = _('Add');
	}

	$maintenancePeriodFormList->addRow(_('Maintenance period'),
		(new CDiv([
			getTimeperiodForm($data),
			new CHorList([
				(new CSimpleButton($save_label))
					->onClick('javascript: submitFormWithParam("'.$maintenanceForm->getName().'", "add_timeperiod", "1");')
					->addClass(ZBX_STYLE_BTN_LINK),
				(new CSimpleButton(_('Cancel')))
					->onClick('javascript: submitFormWithParam("'.$maintenanceForm->getName().'", "cancel_new_timeperiod", "1");')
					->addClass(ZBX_STYLE_BTN_LINK)
			])
		]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	);
}

/*
 * Hosts and groups tab.
 */
$tags = $data['tags'];
if (!$tags) {
	$tags = [['tag' => '', 'operator' => MAINTENANCE_TAG_OPERATOR_LIKE, 'value' => '']];
}

$tag_table = (new CTable())
	->setId('tags')
	->addRow(
		(new CCol(
			(new CRadioButtonList('tags_evaltype', (int) $data['tags_evaltype']))
				->addValue(_('And/Or'), MAINTENANCE_TAG_EVAL_TYPE_AND_OR)
				->addValue(_('Or'), MAINTENANCE_TAG_EVAL_TYPE_OR)
				->setModern(true)
				->setEnabled($data['maintenance_type'] == MAINTENANCE_TYPE_NODATA ? false : true)
		))->setColSpan(4)
	);

$i = 0;
foreach ($tags as $tag) {
	if ($data['maintenance_type'] == MAINTENANCE_TYPE_NODATA) {
		$tag_table->addRow([
			(new CTextBox('tags['.$i.'][tag]', $tag['tag']))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)
				->setEnabled(false),
			(new CRadioButtonList('tags['.$i.'][operator]', (int) $tag['operator']))
				->addValue(_('Contains'), MAINTENANCE_TAG_OPERATOR_LIKE)
				->addValue(_('Equals'), MAINTENANCE_TAG_OPERATOR_EQUAL)
				->setModern(true)
				->setEnabled(false),
			(new CTextBox('tags['.$i.'][value]', $tag['value']))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)
				->setEnabled(false),
			(new CCol(
				(new CButton('tags['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
					->setEnabled(false)
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');
	}
	else {
		$tag_table->addRow([
			(new CTextBox('tags['.$i.'][tag]', $tag['tag']))
				->setAttribute('placeholder', _('tag'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CRadioButtonList('tags['.$i.'][operator]', (int) $tag['operator']))
				->addValue(_('Contains'), MAINTENANCE_TAG_OPERATOR_LIKE)
				->addValue(_('Equals'), MAINTENANCE_TAG_OPERATOR_EQUAL)
				->setModern(true),
			(new CTextBox('tags['.$i.'][value]', $tag['value']))
				->setAttribute('placeholder', _('value'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('tags['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');
	}

	$i++;
}
$tag_table->addRow(
	(new CCol(
		(new CButton('tags_add', _('Add')))
			->addClass(ZBX_STYLE_BTN_LINK)
			->addClass('element-table-add')
			->setEnabled($data['maintenance_type'] == MAINTENANCE_TYPE_NODATA ? false : true)
	))->setColSpan(3)
);

$hostsAndGroupsFormList = (new CFormList('hostsAndGroupsFormList'))
	->addRow('',
		(new CLabel(_('At least one host group or host must be selected.')))->setAsteriskMark()
	)
	->addRow(new CLabel(_('Host groups'), 'groupids__ms'),
		(new CMultiSelect([
			'name' => 'groupids[]',
			'object_name' => 'hostGroup',
			'data' => $data['groups_ms'],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $maintenanceForm->getName(),
					'dstfld1' => 'groupids_',
					'editable' => true
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(new CLabel(_('Hosts'), 'hostids__ms'),
		(new CMultiSelect([
			'name' => 'hostids[]',
			'object_name' => 'hosts',
			'data' => $data['hosts_ms'],
			'popup' => [
				'parameters' => [
					'srctbl' => 'hosts',
					'srcfld1' => 'hostid',
					'dstfrm' => $maintenanceForm->getName(),
					'dstfld1' => 'hostids_',
					'editable' => true
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(_('Tags'),
		(new CDiv($tag_table))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;')
	);

// Append tabs to form.
$maintenanceTab = (new CTabView())
	->addTab('maintenanceTab', _('Maintenance'), $maintenanceFormList)
	->addTab('periodsTab', _('Periods'), $maintenancePeriodFormList)
	->addTab('hostTab', _('Hosts and groups'), $hostsAndGroupsFormList);
if (!$this->data['form_refresh']) {
	$maintenanceTab->setSelected(0);
}

// append buttons to form
if (isset($this->data['maintenanceid'])) {
	$maintenanceTab->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			new CSubmit('clone', _('Clone')),
			new CButtonDelete(_('Delete maintenance period?'), url_param('form').url_param('maintenanceid')),
			new CButtonCancel()
		]
	));
}
else {
	$maintenanceTab->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel()]
	));
}

$maintenanceForm->addItem($maintenanceTab);

$widget->addItem($maintenanceForm);

return $widget;
