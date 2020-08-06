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


$options = [
	'resourcetype' => SCREEN_RESOURCE_PROBLEM,
	'mode' => SCREEN_MODE_JS,
	'dataId' => 'problem',
	'page' => $data['page'],
	'data' => [
		'action' => $data['action'],
		'sort' => $data['sort'],
		'sortorder' => $data['sortorder'],
		'page' => $data['page'],
		'filter' => [
			'show' => $data['filter']['show'],
			'groupids' => $data['filter']['groupids'],
			'hostids' => $data['filter']['hostids'],
			'application' => $data['filter']['application'],
			'triggerids' => $data['filter']['triggerids'],
			'name' => $data['filter']['name'],
			'severity' => $data['filter']['severity'],
			'inventory' => $data['filter']['inventory'],
			'evaltype' => $data['filter']['evaltype'],
			'tags' => $data['filter']['tags'],
			'show_tags' => $data['filter']['show_tags'],
			'tag_name_format' => $data['filter']['tag_name_format'],
			'tag_priority' => $data['filter']['tag_priority'],
			'show_suppressed' => $data['filter']['show_suppressed'],
			'unacknowledged' => $data['filter']['unacknowledged'],
			'compact_view' => $data['filter']['compact_view'],
			'show_timeline' => $data['filter']['show_timeline'],
			'details' => $data['filter']['details'],
			'highlight_row' => $data['filter']['highlight_row'],
			'show_opdata' => $data['filter']['show_opdata']
		]
	]
];

switch ($data['filter']['show']) {
	case TRIGGERS_OPTION_RECENT_PROBLEM:
	case TRIGGERS_OPTION_IN_PROBLEM:
		$options['data']['filter']['age_state'] = $data['filter']['age_state'];
		$options['data']['filter']['age'] = $data['filter']['age'];
		break;

	case TRIGGERS_OPTION_ALL:
		$options['profileIdx'] = $data['profileIdx'];
		$options['profileIdx2'] = $data['profileIdx2'];
		$options['from'] = $data['from'];
		$options['to'] = $data['to'];
		break;
}

$screen = CScreenBuilder::getScreen($options);

if ($data['action'] == 'problem.view') {
	if ($data['filter']['show'] == TRIGGERS_OPTION_ALL) {
		$this->addJsFile('class.calendar.js');
	}
	$this->addJsFile('gtlc.js');
	$this->addJsFile('flickerfreescreen.js');
	$this->addJsFile('multiselect.js');
	$this->addJsFile('layout.mode.js');
	require_once dirname(__FILE__).'/monitoring.problem.view.js.php';

	if ($data['uncheck']) {
		uncheckTableRows('problem');
	}

	$filter_column1 = (new CFormList())
		->addRow(_('Show'),
			(new CRadioButtonList('filter_show', (int) $data['filter']['show']))
				->addValue(_('Recent problems'), TRIGGERS_OPTION_RECENT_PROBLEM)
				->addValue(_('Problems'), TRIGGERS_OPTION_IN_PROBLEM)
				->addValue(_('History'), TRIGGERS_OPTION_ALL)
				->setModern(true)
		)
		->addRow((new CLabel(_('Host groups'), 'filter_groupids__ms')),
			(new CMultiSelect([
				'name' => 'filter_groupids[]',
				'object_name' => 'hostGroup',
				'data' => $data['filter']['groups'],
				'popup' => [
					'parameters' => [
						'srctbl' => 'host_groups',
						'srcfld1' => 'groupid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_groupids_',
						'real_hosts' => true,
						'enrich_parent_groups' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow((new CLabel(_('Hosts'), 'filter_hostids__ms')),
			(new CMultiSelect([
				'name' => 'filter_hostids[]',
				'object_name' => 'hosts',
				'data' => $data['filter']['hosts'],
				'popup' => [
					'parameters' => [
						'srctbl' => 'hosts',
						'srcfld1' => 'hostid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_hostids_'
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Application'), [
			(new CTextBox('filter_application', $data['filter']['application']))
				->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CButton('filter_application_select', _('Select')))
				->onClick('return PopUp("popup.generic",'.
					CJs::encodeJson([
						'srctbl' => 'applications',
						'srcfld1' => 'name',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_application',
						'with_applications' => '1',
						'real_hosts' => '1'
					]).', null, this);'
				)
				->addClass(ZBX_STYLE_BTN_GREY)
		])
		->addRow((new CLabel(_('Triggers'), 'filter_triggerids__ms')),
			(new CMultiSelect([
				'name' => 'filter_triggerids[]',
				'object_name' => 'triggers',
				'data' => $data['filter']['triggers'],
				'popup' => [
					'parameters' => [
						'srctbl' => 'triggers',
						'srcfld1' => 'triggerid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_triggerids_',
						'monitored_hosts' => true,
						'with_monitored_triggers' => true,
						'noempty' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Problem'),
			(new CTextBox('filter_name', $data['filter']['name']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Minimum severity'),
			new CComboBox('filter_severity', $data['filter']['severity'], null, $data['filter']['severities'])
		);

	$filter_age = (new CNumericBox('filter_age', $data['filter']['age'], 3, false, false, false))
		->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH);
	if ($data['filter']['age_state'] == 0) {
		$filter_age->setAttribute('disabled', 'disabled');
	}

	$filter_column1
		->addRow(_('Age less than'), [
			(new CCheckBox('filter_age_state'))
				->setChecked($data['filter']['age_state'] == 1)
				->onClick('javascript: this.checked ? $("filter_age").enable() : $("filter_age").disable()'),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			$filter_age,
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			_('days')
		]);

	$filter_inventory = $data['filter']['inventory'];
	if (!$filter_inventory) {
		$filter_inventory = [['field' => '', 'value' => '']];
	}

	$filter_inventory_table = new CTable();
	$filter_inventory_table->setId('filter-inventory');
	$i = 0;
	foreach ($filter_inventory as $field) {
		$filter_inventory_table->addRow([
			new CComboBox('filter_inventory['.$i.'][field]', $field['field'], null, $data['filter']['inventories']),
			(new CTextBox('filter_inventory['.$i.'][value]', $field['value']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_inventory['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');

		$i++;
	}
	$filter_inventory_table->addRow(
		(new CCol(
			(new CButton('filter_inventory_add', _('Add')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-add')
		))->setColSpan(3)
	);

	$filter_tags = $data['filter']['tags'];
	if (!$filter_tags) {
		$filter_tags = [['tag' => '', 'value' => '', 'operator' => TAG_OPERATOR_LIKE]];
	}

	$filter_tags_table = new CTable();
	$filter_tags_table->setId('filter-tags');

	$filter_tags_table->addRow(
		(new CCol(
			(new CRadioButtonList('filter_evaltype', (int) $data['filter']['evaltype']))
				->addValue(_('And/Or'), TAG_EVAL_TYPE_AND_OR)
				->addValue(_('Or'), TAG_EVAL_TYPE_OR)
				->setModern(true)
		))->setColSpan(4)
	);

	$i = 0;
	foreach ($filter_tags as $tag) {
		$filter_tags_table->addRow([
			(new CTextBox('filter_tags['.$i.'][tag]', $tag['tag']))
				->setAttribute('placeholder', _('tag'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CRadioButtonList('filter_tags['.$i.'][operator]', (int) $tag['operator']))
				->addValue(_('Contains'), TAG_OPERATOR_LIKE)
				->addValue(_('Equals'), TAG_OPERATOR_EQUAL)
				->setModern(true),
			(new CTextBox('filter_tags['.$i.'][value]', $tag['value']))
				->setAttribute('placeholder', _('value'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_tags['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');

		$i++;
	}
	$filter_tags_table->addRow(
		(new CCol(
			(new CButton('filter_tags_add', _('Add')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-add')
		))->setColSpan(3)
	);

	$tag_format_line = (new CHorList())
		->addItem((new CRadioButtonList('filter_show_tags', (int) $data['filter']['show_tags']))
				->addValue(_('None'), PROBLEMS_SHOW_TAGS_NONE)
				->addValue(PROBLEMS_SHOW_TAGS_1, PROBLEMS_SHOW_TAGS_1)
				->addValue(PROBLEMS_SHOW_TAGS_2, PROBLEMS_SHOW_TAGS_2)
				->addValue(PROBLEMS_SHOW_TAGS_3, PROBLEMS_SHOW_TAGS_3)
				->setModern(true)
		)
		->addItem((new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN))
		->addItem(_('Tag name'))
		->addItem((new CRadioButtonList('filter_tag_name_format', (int) $data['filter']['tag_name_format']))
				->addValue(_('Full'), PROBLEMS_TAG_NAME_FULL)
				->addValue(_('Shortened'), PROBLEMS_TAG_NAME_SHORTENED)
				->addValue(_('None'), PROBLEMS_TAG_NAME_NONE)
				->setModern(true)
				->setEnabled((int) $data['filter']['show_tags'] !== PROBLEMS_SHOW_TAGS_NONE)
		);

	$filter_column2 = (new CFormList())
		->addRow(_('Host inventory'), $filter_inventory_table)
		->addRow(_('Tags'), $filter_tags_table)
		->addRow(_('Show tags'), $tag_format_line)
		->addRow(_('Tag display priority'),
			(new CTextBox('filter_tag_priority', $data['filter']['tag_priority']))
				->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
				->setAttribute('placeholder', _('comma-separated list'))
				->setEnabled((int) $data['filter']['show_tags'] !== PROBLEMS_SHOW_TAGS_NONE)
		)
		->addRow(_('Show operational data'), [
			(new CRadioButtonList('filter_show_opdata', (int) $data['filter']['show_opdata']))
				->addValue(_('None'), OPERATIONAL_DATA_SHOW_NONE)
				->addValue(_('Separately'), OPERATIONAL_DATA_SHOW_SEPARATELY)
				->addValue(_('With problem name'), OPERATIONAL_DATA_SHOW_WITH_PROBLEM)
				->setModern(true)
				->setEnabled($data['filter']['compact_view'] == 0)
		])
		->addRow(_('Show suppressed problems'), [
			(new CCheckBox('filter_show_suppressed'))
				->setChecked($data['filter']['show_suppressed'] == ZBX_PROBLEM_SUPPRESSED_TRUE),
			(new CDiv([
				(new CLabel(_('Show unacknowledged only'), 'filter_unacknowledged'))
					->addClass(ZBX_STYLE_SECOND_COLUMN_LABEL),
				(new CCheckBox('filter_unacknowledged'))
					->setChecked($data['filter']['unacknowledged'] == 1)
			]))->addClass(ZBX_STYLE_TABLE_FORMS_SECOND_COLUMN)
		])
		->addRow(_('Compact view'), [
			(new CCheckBox('filter_compact_view'))->setChecked($data['filter']['compact_view'] == 1),
			(new CDiv([
				(new CLabel(_('Show timeline'), 'filter_show_timeline'))->addClass(ZBX_STYLE_SECOND_COLUMN_LABEL),
				(new CCheckBox('filter_show_timeline'))
					->setChecked($data['filter']['show_timeline'] == 1)
					->setEnabled($data['filter']['compact_view'] == 0),
			]))->addClass(ZBX_STYLE_TABLE_FORMS_SECOND_COLUMN)
		])
		->addRow(_('Show details'), [
			(new CCheckBox('filter_details'))
				->setChecked($data['filter']['details'] == 1)
				->setEnabled($data['filter']['compact_view'] == 0),
			(new CDiv([
				(new CLabel(_('Highlight whole row'), 'filter_highlight_row'))->addClass(ZBX_STYLE_SECOND_COLUMN_LABEL),
				(new CCheckBox('filter_highlight_row'))
					->setChecked($data['filter']['highlight_row'] == 1)
					->setEnabled($data['filter']['compact_view'] == 1)
			]))
				->addClass(ZBX_STYLE_FILTER_HIGHLIGHT_ROW_CB)
				->addClass(ZBX_STYLE_TABLE_FORMS_SECOND_COLUMN)
		]);

	$filter = (new CFilter((new CUrl('zabbix.php'))->setArgument('action', 'problem.view')))
		->setProfile($data['profileIdx'])
		->setActiveTab($data['active_tab'])
		->addFormItem((new CVar('action', 'problem.view'))->removeId())
		->addFormItem((new CVar('page', $data['page']))->removeId());

	if ($data['filter']['show'] == TRIGGERS_OPTION_ALL) {
		$filter->addTimeSelector($screen->timeline['from'], $screen->timeline['to']);
	}

	$filter->addFilterTab(_('Filter'), [$filter_column1, $filter_column2]);

	$web_layout_mode = CView::getLayoutMode();

	$widget = (new CWidget())
		->setTitle(_('Problems'))
		->setWebLayoutMode($web_layout_mode)
		->setControls((new CTag('nav', true,
			(new CForm('get'))
				->cleanItems()
				->addVar('action', 'problem.view')
				->addVar('page', $data['page'])
				->addItem((new CList())
					->addItem(new CRedirectButton(_('Export to CSV'),
						(new CUrl('zabbix.php'))
							->setArgument('action', 'problem.view.csv')
							->setArgument('page',  $data['page'])
					))
					->addItem(get_icon('fullscreen'))
				)
			))
				->setAttribute('aria-label', _('Content controls'))
		);

	if (in_array($web_layout_mode, [ZBX_LAYOUT_NORMAL, ZBX_LAYOUT_FULLSCREEN])) {
		$widget->addItem($filter);
	}

	$widget
		->addItem($screen->get())
		->show();

	// activating blinking
	$this->addPostJS('jqBlink.blink();');

	if ($data['filter']['show'] == TRIGGERS_OPTION_ALL) {
		$objData = [
			'id' => 'timeline_1',
			'loadSBox' => 0,
			'loadImage' => 0,
			'dynamic' => 0,
			'mainObject' => 1
		];

		$this->addPostJS('timeControl.addObject("scroll_events_id", '.zbx_jsvalue($screen->timeline).', '.zbx_jsvalue($objData).');');
		$this->addPostJS('timeControl.processObjects();');
	}
}
else {
	echo $screen->get();
}
