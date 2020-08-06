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


/**
 * Graph widget form.
 */
class CWidgetFormGraph extends CWidgetForm {

	public function __construct($data) {
		parent::__construct($data, WIDGET_GRAPH);

		// Select graph type field.
		$field_source = (new CWidgetFieldRadioButtonList('source_type', _('Source'), [
			ZBX_WIDGET_FIELD_RESOURCE_GRAPH => _('Graph'),
			ZBX_WIDGET_FIELD_RESOURCE_SIMPLE_GRAPH => _('Simple graph'),
		]))
			->setDefault(ZBX_WIDGET_FIELD_RESOURCE_GRAPH)
			->setAction('updateWidgetConfigDialogue()')
			->setModern(true);

		if (array_key_exists('source_type', $this->data)) {
			$field_source->setValue($this->data['source_type']);
		}

		$this->fields[$field_source->getName()] = $field_source;

		if (array_key_exists('source_type', $this->data)
				&& $this->data['source_type'] == ZBX_WIDGET_FIELD_RESOURCE_SIMPLE_GRAPH) {
			// Select simple graph field.
			$field_item = (new CWidgetFieldMsItem('itemid', _('Item')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
				->setMultiple(false)
				->setFilterParameter('numeric', true) // For filtering items.
				->setFilterParameter('with_simple_graph_items', true); // For groups and hosts selection.

			if (array_key_exists('itemid', $this->data)) {
				$field_item->setValue($this->data['itemid']);
			}

			$this->fields[$field_item->getName()] = $field_item;
		}
		else {
			// Select graph field.
			$field_graph = (new CWidgetFieldMsGraph('graphid', _('Graph')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
				->setMultiple(false);

			if (array_key_exists('graphid', $this->data)) {
				$field_graph->setValue($this->data['graphid']);
			}

			$this->fields[$field_graph->getName()] = $field_graph;
		}

		// Show legend checkbox.
		$field_legend = (new CWidgetFieldCheckBox('show_legend', _('Show legend')))->setDefault(1);

		if (array_key_exists('show_legend', $this->data)) {
			$field_legend->setValue($this->data['show_legend']);
		}

		$this->fields[$field_legend->getName()] = $field_legend;

		// Dynamic item.
		$field_dynamic = (new CWidgetFieldCheckBox('dynamic', _('Dynamic item')))->setDefault(WIDGET_SIMPLE_ITEM);

		$field_dynamic->setValue(array_key_exists('dynamic', $this->data) ? $this->data['dynamic'] : false);

		$this->fields[$field_dynamic->getName()] = $field_dynamic;
	}
}
