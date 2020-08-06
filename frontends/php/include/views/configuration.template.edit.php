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


require_once dirname(__FILE__).'/js/common.template.edit.js.php';

$widget = (new CWidget())->setTitle(_('Templates'));

if ($data['form'] !== 'clone' && $data['form'] !== 'full_clone') {
	$widget->addItem(get_header_host_table('', $data['templateid']));
}

$divTabs = new CTabView();

if (!hasRequest('form_refresh')) {
	$divTabs->setSelected(0);
}

$host = getRequest('template_name', '');
$visiblename = getRequest('visiblename', '');
$newgroup = getRequest('newgroup', '');
$templateids = getRequest('templates', []);
$clear_templates = getRequest('clear_templates', []);

$frm_title = _('Template');

if ($data['templateid'] != 0) {
	$frm_title .= SPACE.' ['.$data['dbTemplate']['name'].']';
}
$frmHost = (new CForm())
	->setId('templatesForm')
	->setName('templatesForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form']);

if ($data['templateid'] != 0) {
	$frmHost->addVar('templateid', $data['templateid']);
}

if ($data['templateid'] != 0 && !hasRequest('form_refresh')) {
	$host = $data['dbTemplate']['host'];
	$visiblename = $data['dbTemplate']['name'];

	// Display empty visible name if equal to host name.
	if ($visiblename === $host) {
		$visiblename = '';
	}

	$templateids = $data['original_templates'];
}

$clear_templates = array_intersect($clear_templates, array_keys($data['original_templates']));
$clear_templates = array_diff($clear_templates, array_keys($templateids));

natcasesort($templateids);

$frmHost->addVar('clear_templates', $clear_templates);

$templateList = (new CFormList('hostlist'))
	->addRow(
		(new CLabel(_('Template name'), 'template_name'))->setAsteriskMark(),
		(new CTextBox('template_name', $host, false, 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
	)
	->addRow(_('Visible name'), (new CTextBox('visiblename', $visiblename, false, 128))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow((new CLabel(_('Groups'), 'groups__ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'groups[]',
			'object_name' => 'hostGroup',
			'add_new' => (CWebUser::$data['type'] == USER_TYPE_SUPER_ADMIN),
			'data' => $data['groups_ms'],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $frmHost->getName(),
					'dstfld1' => 'groups_',
					'editable' => true
				]
			]
		]))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(_('Description'),
		(new CTextArea('description', $data['description']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setMaxlength(DB::getFieldLength('hosts', 'description'))
	);

if ($data['form'] === 'full_clone') {
	// template applications
	$templateApps = API::Application()->get([
		'hostids' => $data['templateid'],
		'inherited' => false,
		'output' => API_OUTPUT_EXTEND,
		'preservekeys' => true
	]);
	if (!empty($templateApps)) {
		$applicationsList = [];
		foreach ($templateApps as $tplAppId => $templateApp) {
			$applicationsList[$tplAppId] = $templateApp['name'];
		}
		order_result($applicationsList);

		$listBox = (new CListBox('applications', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($applicationsList);
		$templateList->addRow(_('Applications'), $listBox);
	}

	// items
	$hostItems = API::Item()->get([
		'hostids' => $data['templateid'],
		'inherited' => false,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL],
		'output' => ['itemid', 'key_', 'name', 'hostid']
	]);

	if ($hostItems) {
		$hostItems = CMacrosResolverHelper::resolveItemNames($hostItems);

		$itemsList = [];
		foreach ($hostItems as $hostItem) {
			$itemsList[$hostItem['itemid']] = $hostItem['name_expanded'];
		}
		order_result($itemsList);

		$listBox = (new CListBox('items', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($itemsList);

		$templateList->addRow(_('Items'), $listBox);
	}

	// Triggers
	$hostTriggers = API::Trigger()->get([
		'inherited' => false,
		'hostids' => $data['templateid'],
		'output' => API_OUTPUT_EXTEND,
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]]
	]);
	if (!empty($hostTriggers)) {
		$triggersList = [];
		foreach ($hostTriggers as $hostTrigger) {
			$triggersList[$hostTrigger['triggerid']] = $hostTrigger['description'];
		}
		order_result($triggersList);

		$listBox = (new CListBox('triggers', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($triggersList);

		$templateList->addRow(_('Triggers'), $listBox);
	}

	// Graphs
	$hostGraphs = API::Graph()->get([
		'inherited' => false,
		'hostids' => $data['templateid'],
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]],
		'output' => API_OUTPUT_EXTEND,
	]);
	if (!empty($hostGraphs)) {
		$graphsList = [];
		foreach ($hostGraphs as $hostGraph) {
			$graphsList[$hostGraph['graphid']] = $hostGraph['name'];
		}
		order_result($graphsList);

		$listBox = (new CListBox('graphs', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($graphsList);

		$templateList->addRow(_('Graphs'), $listBox);
	}

	// discovery rules
	$hostDiscoveryRules = API::DiscoveryRule()->get([
		'inherited' => false,
		'hostids' => $data['templateid'],
		'output' => API_OUTPUT_EXTEND,
	]);

	if ($hostDiscoveryRules) {
		$hostDiscoveryRules = CMacrosResolverHelper::resolveItemNames($hostDiscoveryRules);

		$discoveryRuleList = [];
		foreach ($hostDiscoveryRules as $discoveryRule) {
			$discoveryRuleList[$discoveryRule['itemid']] = $discoveryRule['name_expanded'];
		}
		order_result($discoveryRuleList);
		$hostDiscoveryRuleids = array_keys($discoveryRuleList);

		$listBox = (new CListBox('discoveryRules', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($discoveryRuleList);

		$templateList->addRow(_('Discovery rules'), $listBox);

		// item prototypes
		$hostItemPrototypes = API::ItemPrototype()->get([
			'hostids' => $data['templateid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'output' => API_OUTPUT_EXTEND,
		]);

		if ($hostItemPrototypes) {
			$hostItemPrototypes = CMacrosResolverHelper::resolveItemNames($hostItemPrototypes);

			$prototypeList = [];
			foreach ($hostItemPrototypes as $itemPrototype) {
				$prototypeList[$itemPrototype['itemid']] = $itemPrototype['name_expanded'];
			}
			order_result($prototypeList);

			$listBox = (new CListBox('itemsPrototypes', null, 8))
				->setAttribute('disabled', 'disabled')
				->addItems($prototypeList);

			$templateList->addRow(_('Item prototypes'), $listBox);
		}

		// Trigger prototypes
		$hostTriggerPrototypes = API::TriggerPrototype()->get([
			'hostids' => $data['templateid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'output' => API_OUTPUT_EXTEND
		]);
		if (!empty($hostTriggerPrototypes)) {
			$prototypeList = [];
			foreach ($hostTriggerPrototypes as $triggerPrototype) {
				$prototypeList[$triggerPrototype['triggerid']] = $triggerPrototype['description'];
			}
			order_result($prototypeList);

			$listBox = (new CListBox('triggerprototypes', null, 8))
				->setAttribute('disabled', 'disabled')
				->addItems($prototypeList);

			$templateList->addRow(_('Trigger prototypes'), $listBox);
		}

		// Graph prototypes
		$hostGraphPrototypes = API::GraphPrototype()->get([
			'hostids' => $data['templateid'],
			'discoveryids' => $hostDiscoveryRuleids,
			'inherited' => false,
			'output' => API_OUTPUT_EXTEND,
		]);
		if (!empty($hostGraphPrototypes)) {
			$prototypeList = [];
			foreach ($hostGraphPrototypes as $graphPrototype) {
				$prototypeList[$graphPrototype['graphid']] = $graphPrototype['name'];
			}
			order_result($prototypeList);

			$listBox = (new CListBox('graphPrototypes', null, 8))
				->setAttribute('disabled', 'disabled')
				->addItems($prototypeList);

			$templateList->addRow(_('Graph prototypes'), $listBox);
		}
	}

	// screens
	$screens = API::TemplateScreen()->get([
		'inherited' => false,
		'templateids' => $data['templateid'],
		'output' => ['screenid', 'name'],
	]);
	if (!empty($screens)) {
		$screensList = [];
		foreach ($screens as $screen) {
			$screensList[$screen['screenid']] = $screen['name'];
		}
		order_result($screensList);

		$listBox = (new CListBox('screens', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($screensList);

		$templateList->addRow(_('Screens'), $listBox);
	}

	// web scenarios
	$httpTests = API::HttpTest()->get([
		'output' => ['httptestid', 'name'],
		'hostids' => $data['templateid'],
		'inherited' => false
	]);

	if ($httpTests) {
		$httpTestList = [];

		foreach ($httpTests as $httpTest) {
			$httpTestList[$httpTest['httptestid']] = $httpTest['name'];
		}

		order_result($httpTestList);

		$listBox = (new CListBox('httpTests', null, 8))
			->setAttribute('disabled', 'disabled')
			->addItems($httpTestList);
		$templateList->addRow(_('Web scenarios'), $listBox);
	}
}

$cloneOrFullClone = ($data['form'] === 'clone' || $data['form'] === 'full_clone');

$divTabs->addTab('templateTab', _('Template'), $templateList);

$tmplList = new CFormList();

$disableids = [];

$linkedTemplateTable = (new CTable())
	->setHeader([_('Name'), _('Action')])
	->addStyle('width: 100%;');

foreach ($data['linked_templates'] as $template) {
	$tmplList->addItem((new CVar('templates['.$template['templateid'].']', $template['templateid']))->removeId());

	if (array_key_exists($template['templateid'], $data['writable_templates'])) {
		$template_link = (new CLink($template['name'], 'templates.php?form=update&templateid='.$template['templateid']))
			->setTarget('_blank');
	}
	else {
		$template_link = new CSpan($template['name']);
	}

	$linkedTemplateTable->addRow([
		$template_link,
		(new CCol(
			new CHorList([
				(new CSimpleButton(_('Unlink')))
					->onClick('javascript: submitFormWithParam('.
						'"'.$frmHost->getName().'", "unlink['.$template['templateid'].']", "1"'.
					');')
					->addClass(ZBX_STYLE_BTN_LINK),
				(array_key_exists($template['templateid'], $data['original_templates']) && !$cloneOrFullClone)
					? (new CSimpleButton(_('Unlink and clear')))
						->onClick('javascript: submitFormWithParam('.
							'"'.$frmHost->getName().'", "unlink_and_clear['.$template['templateid'].']", "1"'.
						');')
						->addClass(ZBX_STYLE_BTN_LINK)
					: null
			])
		))->addClass(ZBX_STYLE_NOWRAP)
	], null, 'conditions_'.$template['templateid']);

	$disableids[] = $template['templateid'];
}

$add_templates_ms = (new CMultiSelect([
	'name' => 'add_templates[]',
	'object_name' => 'templates',
	'data' => $data['add_templates'],
	'popup' => [
		'parameters' => [
			'srctbl' => 'templates',
			'srcfld1' => 'hostid',
			'srcfld2' => 'host',
			'dstfrm' => $frmHost->getName(),
			'dstfld1' => 'add_templates_',
			'excludeids' => ($data['templateid'] == 0) ? [] : [$data['templateid']],
			'disableids' => $disableids
		]
	]
]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

$tmplList
	->addRow(_('Linked templates'),
		(new CDiv($linkedTemplateTable))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	)
	->addRow((new CLabel(_('Link new templates'), 'add_templates__ms')),
		(new CDiv(
			(new CTable())->addRow([$add_templates_ms])
		))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	);

$divTabs->addTab('tmplTab', _('Linked templates'), $tmplList);

// tags
$tags_view = new CView('configuration.tags.tab', [
	'source' => 'template',
	'tags' => $data['tags'],
	'readonly' => $data['readonly']
]);

$divTabs->addTab('tags-tab', _('Tags'), $tags_view->render());

// macros
$divTabs->addTab('macroTab', _('Macros'),
	(new CFormList('macrosFormList'))
		->addRow(null, (new CRadioButtonList('show_inherited_macros', (int) $data['show_inherited_macros']))
			->addValue(_('Template macros'), 0)
			->addValue(_('Inherited and template macros'), 1)
			->setModern(true)
		)
		->addRow(null, new CObject((new CView('hostmacros.list.html', [
			'macros' => $data['macros'],
			'show_inherited_macros' => $data['show_inherited_macros'],
			'readonly' => $data['readonly']
		]))->getOutput()), 'macros_container')
);

// footer
if ($data['templateid'] != 0 && $data['form'] !== 'full_clone') {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			new CSubmit('clone', _('Clone')),
			new CSubmit('full_clone', _('Full clone')),
			new CButtonDelete(_('Delete template?'), url_param('form').url_param('templateid').url_param('groupid')),
			new CButtonQMessage(
				'delete_and_clear',
				_('Delete and clear'),
				_('Delete and clear template? (Warning: all linked hosts will be cleared!)'),
				url_param('form').url_param('templateid').url_param('groupid')
			),
			new CButtonCancel(url_param('groupid'))
		]
	));
}
else {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('groupid'))]
	));
}

$frmHost->addItem($divTabs);

$widget->addItem($frmHost);

return $widget;
