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


require_once dirname(__FILE__).'/js/administration.general.macros.edit.js.php';

$widget = (new CWidget())
	->setTitle(_('Macros'))
	->setControls((new CTag('nav', true,
		(new CForm())
			->cleanItems()
			->addItem((new CList())
				->addItem(makeAdministrationGeneralMenu('adm.macros.php'))
			)
		))
			->setAttribute('aria-label', _('Content controls'))
	);

$table = (new CTable())
	->setId('tbl_macros')
	->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_CONTAINER)
	->setHeader([_('Macro'), '', _('Value'), _('Description'), '']);

// fields
foreach ($data['macros'] as $i => $macro) {
	$macro_input = (new CTextAreaFlexible('macros['.$i.'][macro]', $macro['macro']))
		->addClass('macro')
		->setWidth(ZBX_TEXTAREA_MACRO_WIDTH)
		->setAttribute('placeholder', '{$MACRO}');

	if ($i == 0) {
		$macro_input->setAttribute('autofocus', 'autofocus');
	}

	$value_input = (new CTextAreaFlexible('macros['.$i.'][value]', $macro['value']))
		->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
		->setAttribute('placeholder', _('value'));

	$description_input = (new CTextAreaFlexible('macros['.$i.'][description]', $macro['description']))
		->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
		->setMaxlength(DB::getFieldLength('globalmacro', 'description'))
		->setAttribute('placeholder', _('description'));

	$button_cell = [
		(new CButton('macros['.$i.'][remove]', _('Remove')))
			->addClass(ZBX_STYLE_BTN_LINK)
			->addClass('element-table-remove')
	];
	if (array_key_exists('globalmacroid', $macro)) {
		$button_cell[] = new CVar('macros['.$i.'][globalmacroid]', $macro['globalmacroid']);
	}

	$table->addRow([
		(new CCol($macro_input))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		'&rArr;',
		(new CCol($value_input))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		(new CCol($description_input))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
		(new CCol($button_cell))->addClass(ZBX_STYLE_NOWRAP)
	], 'form_row');
}

// buttons
$table->setFooter(new CCol(
	(new CButton('macro_add', _('Add')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->addClass('element-table-add')
));

// form list
$macros_form_list = (new CFormList('macrosFormList'))->addRow($table);

$tab_view = (new CTabView())->addTab('macros', _('Macros'), $macros_form_list);

$saveButton = (new CSubmit('update', _('Update')))->setAttribute('data-removed-count', 0);

$tab_view->setFooter(makeFormFooter($saveButton));

$form = (new CForm())
	->setName('macrosForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addItem($tab_view);

$widget->addItem($form);

return $widget;
